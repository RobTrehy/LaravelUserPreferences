<?php

namespace RobTrehy\LaravelUserPreferences;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class UserPreferences
 *
 * This class handles the user preferences in the application.
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
     * Check for preferences, load if not already loaded for this user.
     *
     * @param  int|string|null  $userId
     */
    protected static function preferencesLoaded(mixed $userId = null)
    {
        $userId = $userId ?? Auth::id();
        if (array_key_exists($userId, self::$preferencesCache)) {
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
     * @param  int|string|null  $userId
     */
    protected static function getPreferences(mixed $userId = null)
    {
        $userId = $userId ?? Auth::id();

        // Wrap the raw JSON value so a null column is cached as a hit.
        // Use an array to avoid caching objects that require unserialization.
        $column = config('user-preferences.database.column');
        $cachedPreferences = Cache::rememberForever(
            self::cacheKey($userId),
            function () use ($userId, $column) {
                return ['raw' => DB::table(config('user-preferences.database.table'))
                    ->where(config('user-preferences.database.primary_key'), $userId)
                    ->value($column)];
            }
        );

        $rawPreferences = self::unwrapCachedPreferences($cachedPreferences);

        if (is_null($rawPreferences)) {
            // No row or column is null → use defaults
            self::$preferencesCache[$userId] = (object) config('user-preferences.defaults');
        } else {
            $preferences = json_decode($rawPreferences);
            if (json_last_error() !== JSON_ERROR_NONE || is_null($preferences)) {
                // Invalid JSON → fallback to defaults
                self::$preferencesCache[$userId] = (object) config('user-preferences.defaults');
            } else {
                self::$preferencesCache[$userId] = $preferences;
            }
        }

    }

    /**
     * Read both enveloped values and existing raw JSON cache entries.
     */
    protected static function unwrapCachedPreferences(mixed $cachedPreferences): mixed
    {
        return is_array($cachedPreferences) ? $cachedPreferences['raw'] : $cachedPreferences;
    }

    /**
     * Set the default preferences for a user
     *
     * @param  int|string|null  $userId
     */
    public static function setDefaultPreferences(mixed $userId = null)
    {
        self::preferencesLoaded($userId);
        self::save($userId);
    }

    /**
     * Get a preference by key.
     *
     * Returns the preference if set, otherwise returns the default value from config.
     *
     * @param  int|string|null  $userId
     * @return mixed
     */
    public static function get(string $key, mixed $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();
        if (isset(self::$preferencesCache[$userId]->{$key})) {
            return self::$preferencesCache[$userId]->{$key};
        }

        return config('user-preferences.defaults.'.$key, null);
    }

    /**
     * Set a preference by key.
     *
     * Flushes the cache for this key/user so it's loaded next time.
     * Checks the data type against defaults and saves to the database.
     *
     * @param  mixed  $value
     * @param  int|string|null  $userId
     */
    public static function set(string $key, $value, mixed $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();

        if (
            config('user-preferences.defaults.'.$key)
            && gettype($value) !== gettype(config('user-preferences.defaults.'.$key))
        ) {
            throw new InvalidArgumentException(
                'The expected type is "'.gettype(config('user-preferences.defaults.'.$key)).
                    '"! "'.gettype($value).'" was given.'
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
     * @param  int|string|null  $userId
     * @return bool
     */
    public static function reset(string $key, mixed $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();

        if (config('user-preferences.defaults.'.$key)) {
            self::$preferencesCache[$userId]->{$key} = config('user-preferences.defaults.'.$key);
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
     * @param  int|string|null  $userId
     * @return bool
     */
    public static function has(string $key, mixed $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();

        return isset(self::$preferencesCache[$userId]->{$key});
    }

    /**
     * Get all preferences as an object.
     *
     * @param  int|string|null  $userId
     * @return mixed
     */
    public static function all(mixed $userId = null)
    {
        self::preferencesLoaded($userId);
        $userId = $userId ?? Auth::id();

        return json_decode(json_encode(self::$preferencesCache[$userId]), true);
    }

    /**
     * Save all preferences to the database.
     *
     * @param  int|string|null  $userId
     */
    protected static function save(mixed $userId = null)
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
     * @param  int|string|null  $userId
     */
    protected static function resetCache(mixed $userId = null)
    {
        $userId = $userId ?? Auth::id();
        Cache::forget(self::cacheKey($userId));
    }

    /**
     * Build the shared cache key for a resolved user ID.
     *
     * @param  int|string|null  $userId
     */
    protected static function cacheKey(mixed $userId): string
    {
        return config('user-preferences.cache.prefix').$userId.config('user-preferences.cache.suffix');
    }

    // ------------------------------------------------------------------
    // Methods supporting arbitrary user access
    // ------------------------------------------------------------------

    /**
     * Get preferences for a specific user instance or ID.
     *
     * @param  Authenticatable|int|null  $user
     * @return mixed
     */
    public static function getForUser(string $key, $user)
    {
        $userId = $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user;

        return self::get($key, $userId);
    }

    /**
     * Set preference for a specific user instance or ID.
     *
     * @param  mixed  $value
     * @param  Authenticatable|int|null  $user
     */
    public static function setForUser(string $key, $value, $user)
    {
        $userId = $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user;
        self::set($key, $value, $userId);
    }

    /**
     * Reset preference for a specific user instance or ID.
     *
     * @param  Authenticatable|int|null  $user
     * @return bool
     */
    public static function resetForUser(string $key, $user)
    {
        $userId = $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user;

        return self::reset($key, $userId);
    }

    /**
     * Check if a preference exists for a specific user instance or ID.
     *
     * @param  Authenticatable|int|null  $user
     * @return bool
     */
    public static function hasForUser(string $key, $user)
    {
        $userId = $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user;

        return self::has($key, $userId);
    }
}
