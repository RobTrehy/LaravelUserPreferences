<?php

namespace RobTrehy\LaravelUserPreferences;

use Illuminate\Support\Facades\Auth;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use DB;

/**
 * Class UserPreferences
 *
 * This class handles the user preferences in the application.
 *
 * @package RobTrehy\LaravelUserPreferences
 */
class UserPreferences {
    /**
     * The cache of the user's preferences
     *
     * @var object
     */
    protected static $preferences;

    protected static $hasLoaded;

    /**
     * UserPreferences Constructor
     */
    public function __construct() {}

    /**
     * UserPreferences destructor
     */
    public function __destruct() {}

    /**
     * Check for preferences, load if not
     */
    protected static function preferencesLoaded()
    {
        if (self::$hasLoaded)
            return;
        self::getPreferences();
    }

    /**
     * Get all preferences of the currently authenticated user from the database
     *
     * If the preferences column is empty, the default preferences will be loaded from config
     */
    protected static function getPreferences()
    {
        self::$hasLoaded = true;

        $data = DB::table(config('user-preferences.database.table'))
            ->select(config('user-preferences.database.column'))
            ->where(config('user-preferences.database.primary_key'), '=', Auth::id())
            ->get();

        $preferences = json_decode($data[0]->settings);

        if (json_last_error() !== JSON_ERROR_NONE) {
            self::$preferences = (object) config('user-preferences.defaults');
            return;
        }

        self::$preferences = $preferences;
    }

    /**
     * This method sets the default preferences for a user when the account is created
     */
    public static function setDefaultPreferences()
    {
        self::preferencesLoaded();
        self::save();
    }

    /**
     * Get a preference by key
     *
     * This function will return the preference by its key.
     * If a value does not exist, the default value will be returned
     *
     * @param string $key
     * @return mixed
     */
    public static function get(string $key)
    {
        self::preferencesLoaded();

        if (isset(self::$preferences->{$key}))
            return self::$preferences->{$key};

        else if (config('user-preferences.defaults.' . $key))
            return config('user-preferences.defaults.' . $key);

        else
            return null;
    }

    /**
     * Set the preference by key
     *
     * This function will check the data type with the defaults and save to the database
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value)
    {
        self::preferencesLoaded();

        if (config('user-preferences.defaults.' . $key) && gettype($value) !== gettype(config('user-preferences.defaults.' . $key)))
            throw new InvalidArgumentException(('The expected type is "' . gettype(config('user-preferences.defaults.' . $key)) . '"! "' . gettype($value) . '" was given.'));

        self::$preferences->{$key} = $value;
        self::save();
    }

    /**
     * Reset a preference
     *
     * This function will restore the default value - function will return true.
     * If no default value exists preference will be deleted and function will return false.
     *
     * @param string $key
     * @return bool
     */
    public static function reset(string $key)
    {
        self::preferencesLoaded();

        if (config('user-preferences.defaults.' . $key))
        {
            self::$preferences->{$key} = config('user-preferences.defaults.' . $key);
            self::save();
            return true;
        }

        unset(self::$preferences->{$key});
        self::save();
        return false;
    }

    /**
     * Returns true if preference exists in the variable
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key)
    {
        self::preferencesLoaded();
        return (isset(self::$preferences->{$key}));
    }

    /**
     * Returns all preferences as an object
     *
     * @return object
     */
    public static function all()
    {
        self::preferencesLoaded();
        return self::$preferences;
    }

    /**
     * Save all preferences to database
     */
    protected static function save()
    {
        DB::table(config('user-preferences.database.table'))
            ->where(config('user-preferences.database.primary_key'), '=', Auth::id())
            ->update([config('user-preferences.database.column') => json_encode(self::$preferences)]);
    }

}