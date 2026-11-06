<?php

namespace RobTrehy\LaravelUserPreferences;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
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

    public function packageBooted(): void
    {
        $clearLocalCache = static fn () => UserPreferences::clearLocalCache();

        $this->app['events']->listen([
            RequestHandled::class,
            JobProcessing::class,
            JobProcessed::class,
            JobExceptionOccurred::class,
            // Event names keep Octane optional for applications using this package.
            'Laravel\\Octane\\Events\\RequestReceived',
            'Laravel\\Octane\\Events\\RequestTerminated',
            'Laravel\\Octane\\Events\\TaskReceived',
            'Laravel\\Octane\\Events\\TaskTerminated',
        ], $clearLocalCache);

        $this->app->terminating($clearLocalCache);
    }
}
