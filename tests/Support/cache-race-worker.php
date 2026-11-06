<?php

require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Foundation\Application;
use RobTrehy\LaravelUserPreferences\UserPreferences;
use RobTrehy\LaravelUserPreferences\UserPreferencesServiceProvider;

$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$app = Application::create();
$app['config']->set('database.connections.race', ['driver' => 'sqlite', 'database' => $input['database'], 'prefix' => '', 'busy_timeout' => 5000]);
$app['config']->set('database.default', 'race');
$app['config']->set('cache.stores.race', ['driver' => 'file', 'path' => $input['directory'].'/cache']);
$app['config']->set('cache.default', 'race');
$app->register(UserPreferencesServiceProvider::class);

if ($input['role'] === 'reader') {
    $paused = false;
    DB::listen(function ($event) use ($input, &$paused) {
        if ($paused || ! str_starts_with($event->sql, 'select "preferences"')) {
            return;
        }
        $paused = true;
        file_put_contents($input['directory'].'/read', 'ready');
        $deadline = microtime(true) + 15;
        while (! is_file($input['directory'].'/release')) {
            if (microtime(true) >= $deadline) {
                throw new RuntimeException('Reader was not released by the test');
            }
            usleep(10000);
        }
    });
    UserPreferences::get('theme', 1);
} else {
    UserPreferences::set('theme', 'new', 1);
}
