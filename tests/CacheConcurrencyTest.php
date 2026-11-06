<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RobTrehy\LaravelUserPreferences\UserPreferences;
use Symfony\Component\Process\Process;

it('invalidates a stale cache fill that overlaps a committed write', function ($rawPreferences, $version) {
    $directory = sys_get_temp_dir().'/preferences-race-'.bin2hex(random_bytes(8));
    $files = new Filesystem;
    $files->makeDirectory($directory);
    $database = $directory.'/database.sqlite';
    touch($database);
    $originalConnection = DB::getDefaultConnection();
    $originalCache = config('cache.default');
    config()->set('database.connections.race', ['driver' => 'sqlite', 'database' => $database, 'prefix' => '', 'busy_timeout' => 5000]);
    config()->set('cache.stores.race', ['driver' => 'file', 'path' => $directory.'/cache']);
    config()->set('cache.default', 'race');
    DB::setDefaultConnection('race');
    $db = DB::connection();
    $reader = $writer = null;

    $waitFor = static function (Closure $condition, Process $process): void {
        $deadline = microtime(true) + 10;
        do {
            if ($condition()) {
                return;
            }
            if (! $process->isRunning()) {
                throw new RuntimeException('Worker exited early: '.$process->getErrorOutput().$process->getOutput());
            }
            usleep(10000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException('Timed out waiting for worker: '.$process->getErrorOutput());
    };
    $startWorker = static function (string $role) use ($directory, $database): Process {
        $process = new Process([PHP_BINARY, __DIR__.'/Support/cache-race-worker.php']);
        $process->setInput(json_encode(compact('role', 'directory', 'database'), JSON_THROW_ON_ERROR));
        $process->setTimeout(25);
        $process->start();

        return $process;
    };

    try {
        $db->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, preferences TEXT)');
        $db->table('users')->insert(['id' => 1, 'preferences' => $rawPreferences]);
        if ($version !== null) {
            Cache::forever('user-1-preferences:version', $version);
        }
        $reader = $startWorker('reader');
        $waitFor(static fn () => is_file($directory.'/read'), $reader);

        $writer = $startWorker('writer');
        // The write must commit while the reader still holds the old result.
        $waitFor(static fn () => $db->table('users')->where('id', 1)->value('preferences') === '{"theme":"new"}', $writer);
        $writer->wait();
        expect($writer->getExitCode())->toBe(0, $writer->getErrorOutput());
        expect(Cache::get('user-1-preferences'))->toBeNull();

        file_put_contents($directory.'/release', 'continue');
        $reader->wait();
        expect($reader->getExitCode())->toBe(0, $reader->getErrorOutput());
        expect($writer->getExitCode())->toBe(0, $writer->getErrorOutput());

        expect(Cache::get('user-1-preferences'))->toBeNull();
        UserPreferences::clearLocalCache();
        expect(UserPreferences::get('theme', 1))->toBe('new');
        expect(Cache::get('user-1-preferences'))->toBe(['raw' => '{"theme":"new"}']);
    } finally {
        try {
            $reader?->stop();
            $writer?->stop();
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('race');
            config()->set('cache.default', $originalCache);
            UserPreferences::clearLocalCache();
            $files->deleteDirectory($directory);
        }
    }
})->with([
    'populated preferences' => ['{"theme":"old"}', null],
    'empty preferences' => [null, null],
    'previously invalidated preferences' => ['{"theme":"old"}', 'previous-version'],
]);
