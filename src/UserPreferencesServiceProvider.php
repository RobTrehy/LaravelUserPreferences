<?php

namespace RobTrehy\LaravelUserPreferences;

use Illuminate\Support\ServiceProvider;

class UserPreferencesServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // Export the config
            $this->publishes([
                __DIR__ . '/config/user-preferences.php' => config_path('user-preferences.php'),
            ], 'config');

            // Export the migration
            if (! class_exists('AddPreferencesToTable')) {
                $this->publishes([
                    __DIR__ . '/database/migrations/add_preferences_to_table.php.stub'
                    => database_path('migrations/' . date('Y_m_d_His', time()) . '_add_preferences_to_table.php'),
                ], 'migrations');
            }
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(UserPreferences::class, function () {
            return new UserPreferences();
        });

        $this->app->alias(UserPreferences::class, 'user-preferences');

        $this->mergeConfigFrom(__DIR__.'/config/user-preferences.php', 'user-preferences');
    }
}
