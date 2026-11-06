<?php

require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Foundation\Application;
use RobTrehy\LaravelUserPreferences\UserPreferences;
use RobTrehy\LaravelUserPreferences\UserPreferencesServiceProvider;

$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$app = Application::create();
$app['config']->set('database.connections.concurrency', $input['connection']);
$app['config']->set('database.default', 'concurrency');
$app['config']->set('cache.default', 'array');
$app->register(UserPreferencesServiceProvider::class);
config()->set('user-preferences.database.table', $input['table']);
config()->set('user-preferences.defaults', ['second' => 'default']);
DB::statement("SET lock_timeout = '15s'");
DB::statement("SET statement_timeout = '20s'");

// Load the old committed document before attempting the blocked write.
UserPreferences::all(1);
echo DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n";
flush();

if ($input['operation'] === 'set') {
    UserPreferences::set('second', 'changed', 1);
} else {
    UserPreferences::reset($input['operation'] === 'reset' ? 'second' : 'custom', 1);
}
