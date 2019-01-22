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
        $this->publishes([
            __DIR__ . '/config/user-preferences.php' => config_path('user-preferences.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/database/migrations' => base_path('/database/migrations'),
        ], 'migrations');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(UserPreferences::class, function() {
            return new UserPreferences();
        });

        $this->app->alias(UserPreferences::class, 'user-preferences');

        if ($this->app->config->get('user-preferences') === null) {
            $this->app->config->set('user-preferences', require __DIR__ . '/config/user-preferences.php');
        }
    }
}