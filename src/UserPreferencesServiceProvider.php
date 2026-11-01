<?php

namespace RobTrehy\LaravelUserPreferences;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UserPreferencesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-user-preferences')
            ->hasConfigFile('user-preferences')
            ->hasMigrations([
                'add_preferences_to_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('user-preferences', fn () => new UserPreferences);

        $this->app->alias('user-preferences', UserPreferences::class);
    }
}
