<?php

namespace RobTrehy\LaravelUserPreferences;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class UserPreferences
 *
 * This class handles the user preferences in the application.
 *
 * @package RobTrehy\LaravelUserPreferences
 */
class UserPreferences
{
    /**
     * The cache of the user's preferences per user ID
     *
     * @var array<int, object>
     */
    protected static $preferencesCache = [];

    /**
     * Tracks which user IDs have loaded preferences
     *
     * @var array<int, bool>
     */
    protected static $hasLoadedCache = [];

    /**
     * The ID of the user being scoped
     *
     * @var int|null
     */
    protected $userId;

    /**
     * Check for preferences, load if not already loaded for this user.
     *
     * @param int|null $userId
     */
    protected static function preferencesLoaded(?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        if (self::$hasLoadedCache[$userId] ?? false) {
            return;
        }
        self::getPreferences($userId);
    }

    /**
     * Get all preferences of the specified user.
     *
     * If it's already been loaded, it will exist in cache.
     * If not, it will be loaded into the cache.
     *
     * If the preferences column is empty, the default preferences will be loaded from config.
     *
     * @param int|null $userId
     */
    protected static function getPreferences(?int $userId = null)
    {
        $userId = $userId ?? Auth::id();

        $data = Cache::rememberForever(
            config('user-preferences.cache.prefix') . $userId . config('user-preferences.cache.suffix'),
            function () use ($userId) {
                return DB::table(config('user-preferences.database.table'))
                    ->select(config('user-preferences.database.column'))
                    ->where(config('user-preferences.database.primary_key'), $userId)
                    ->get();
            }
        );

        if ($data->isEmpty()) {
            self::$preferencesCache[$userId] = (object) config('user-preferences.defaults');
        } else {
            $preferences = json_decode($data[0]->{config('user-preferences.database.column')} ?? '{}');
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::$preferencesCache[$userId] = (object) config('user-preferences.defaults');
            } else {
                self::$preferencesCache[$userId] = $preferences;
            }
        }

        self::$hasLoadedCache[$userId] = true;
    }

    /**
     * Set the default preferences for a user
     *
     * @param int|null $userId
     */
    public static function setDefaultPreferences(?int $userId = null)
    {
        self::preferencesLoaded($userId);
        self::save($userId);
    }

    /**
     * Get a preference by key.
     *
     * Returns the preference if set, otherwise returns the default value from config.
     *
     * @param string $key
     * @param int|null $userId
     * @return mixed
     */
    public static function get(string $key, ?int $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();
        if (isset(self::$preferencesCache[$userId]->{$key})) {
            return self::$preferencesCache[$userId]->{$key};
        }
        return config('user-preferences.defaults.' . $key, null);
    }

    /**
     * Set a preference by key.
     *
     * Flushes the cache for this key/user so it's loaded next time.
     * Checks the data type against defaults and saves to the database.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $userId
     */
    public static function set(string $key, $value, ?int $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();

        if (config('user-preferences.defaults.' . $key)
            && gettype($value) !== gettype(config('user-preferences.defaults.' . $key))
        ) {
            throw new InvalidArgumentException(
                'The expected type is "' . gettype(config('user-preferences.defaults.' . $key)) .
                '"! "' . gettype($value) . '" was given.'
            );
        }

        self::$preferencesCache[$userId]->{$key} = $value;
        self::save($userId);
    }

    /**
     * Reset a preference to its default value.
     *
     * Returns true if restored to default, false if preference was deleted.
     *
     * @param string $key
     * @param int|null $userId
     * @return bool
     */
    public static function reset(string $key, ?int $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();

        if (config('user-preferences.defaults.' . $key)) {
            self::$preferencesCache[$userId]->{$key} = config('user-preferences.defaults.' . $key);
            self::save($userId);
            return true;
        }

        unset(self::$preferencesCache[$userId]->{$key});
        self::save($userId);
        return false;
    }

    /**
     * Check if a preference exists for a user.
     *
     * @param string $key
     * @param int|null $userId
     * @return bool
     */
    public static function has(string $key, ?int $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();
        return isset(self::$preferencesCache[$userId]->{$key});
    }

    /**
     * Get all preferences as an object.
     *
     * @param int|null $userId
     * @return object
     */
    public static function all(?int $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();
        return self::$preferencesCache[$userId];
    }

    /**
     * Save all preferences to the database.
     *
     * @param int|null $userId
     */
    protected static function save(?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        DB::table(config('user-preferences.database.table'))
            ->where(config('user-preferences.database.primary_key'), $userId)
            ->update([config('user-preferences.database.column') => json_encode(self::$preferencesCache[$userId])]);

        self::resetCache($userId);
    }

    /**
     * Reset the cache for a given user.
     *
     * @param int|null $userId
     */
    protected static function resetCache(?int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        Cache::forget(config('user-preferences.cache.prefix') . $userId . config('user-preferences.cache.suffix'));
    }

    // ------------------------------------------------------------------
    // Methods supporting arbitrary user access
    // ------------------------------------------------------------------

    /**
     * Get preferences for a specific user instance or ID.
     *
     * @param string $key
     * @param \Illuminate\Contracts\Auth\Authenticatable|int|null $user
     * @return mixed
     */
    public static function getForUser(string $key, $user)
    {
        $userId = $user instanceof \Illuminate\Contracts\Auth\Authenticatable ? $user->getAuthIdentifier() : $user;
        return self::get($key, $userId);
    }

    /**
     * Set preference for a specific user instance or ID.
     *
     * @param string $key
     * @param mixed $value
     * @param \Illuminate\Contracts\Auth\Authenticatable|int|null $user
     */
    public static function setForUser(string $key, $value, $user)
    {
        $userId = $user instanceof \Illuminate\Contracts\Auth\Authenticatable ? $user->getAuthIdentifier() : $user;
        self::set($key, $value, $userId);
    }

    /**
     * Reset preference for a specific user instance or ID.
     *
     * @param string $key
     * @param \Illuminate\Contracts\Auth\Authenticatable|int|null $user
     * @return bool
     */
    public static function resetForUser(string $key, $user)
    {
        $userId = $user instanceof \Illuminate\Contracts\Auth\Authenticatable ? $user->getAuthIdentifier() : $user;
        return self::reset($key, $userId);
    }

    /**
     * Check if a preference exists for a specific user instance or ID.
     *
     * @param string $key
     * @param \Illuminate\Contracts\Auth\Authenticatable|int|null $user
     * @return bool
     */
    public static function hasForUser(string $key, $user)
    {
        $userId = $user instanceof \Illuminate\Contracts\Auth\Authenticatable ? $user->getAuthIdentifier() : $user;
        return self::has($key, $userId);
    }
}
