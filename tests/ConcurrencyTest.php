<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use RobTrehy\LaravelUserPreferences\UserPreferences;
use Symfony\Component\Process\Process;

it('serializes concurrent preference mutations without losing committed keys', function ($operation) {
    if (getenv('PREFERENCES_CONCURRENCY_TESTS') !== '1') {
        test()->markTestSkipped('PostgreSQL concurrency tests run in the dedicated CI job.');
    }

    $connection = [
        'driver' => 'pgsql',
        'host' => getenv('CONCURRENCY_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('CONCURRENCY_DB_PORT') ?: '5432',
        'database' => 'preferences_test',
        'username' => 'postgres',
        'password' => 'postgres',
        'charset' => 'utf8',
        'prefix' => '',
        'search_path' => 'public',
        'sslmode' => 'prefer',
    ];
    config()->set('database.connections.concurrency', $connection);
    DB::setDefaultConnection('concurrency');
    $db = DB::connection();
    $table = 'preferences_concurrency_'.bin2hex(random_bytes(6));
    config()->set('user-preferences.database.table', $table);
    UserPreferences::clearLocalCache();
    $process = null;

    try {
        $db->getSchemaBuilder()->create($table, function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('preferences')->nullable();
        });
        $db->table($table)->insert(['id' => 1, 'preferences' => '{"second":"old","custom":"remove"}']);
        $db->beginTransaction();
        $db->table($table)->where('id', 1)->lockForUpdate()->first();
        UserPreferences::set('first', 'committed', 1);
        $parentPid = (int) $db->selectOne('SELECT pg_backend_pid() AS pid')->pid;

        $process = new Process([PHP_BINARY, __DIR__.'/Support/concurrent-writer.php']);
        $process->setInput(json_encode(compact('connection', 'table', 'operation'), JSON_THROW_ON_ERROR));
        $process->setTimeout(30);
        $process->start();

        // Wait for evidence from PostgreSQL that B is blocked by A, rather
        // than assuming overlap after an arbitrary delay.
        $deadline = microtime(true) + 15;
        $blocked = false;
        while (microtime(true) < $deadline && $process->isRunning()) {
            $output = trim($process->getOutput());
            if (ctype_digit($output)) {
                $row = $db->selectOne('SELECT ? = ANY(pg_blocking_pids(CAST(? AS integer))) AS blocked', [$parentPid, (int) $output]);
                if ($row->blocked) {
                    $blocked = true;
                    break;
                }
            }
            usleep(10000);
        }
        expect($blocked)->toBeTrue($process->getErrorOutput().$process->getOutput());
        $db->commit();
        $process->wait();
        expect($process->getExitCode())->toBe(0, $process->getErrorOutput());

        $stored = json_decode($db->table($table)->where('id', 1)->value('preferences'), true, flags: JSON_THROW_ON_ERROR);
        expect($stored['first'])->toBe('committed');
        expect($stored['second'])->toBe(match ($operation) {
            'set' => 'changed',
            'reset' => 'default',
            default => 'old',
        });
        expect(array_key_exists('custom', $stored))->toBe($operation !== 'delete');
    } finally {
        if ($db->transactionLevel() > 0) {
            $db->rollBack(0);
        }
        $process?->stop();
        $db->getSchemaBuilder()->dropIfExists($table);
        UserPreferences::clearLocalCache();
        DB::setDefaultConnection('testing');
        DB::purge('concurrency');
    }
})->with(['set', 'reset', 'delete'])->group('concurrency');
