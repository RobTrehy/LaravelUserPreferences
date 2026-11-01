
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RobTrehy\LaravelUserPreferences\Tests\Models\User;
use RobTrehy\LaravelUserPreferences\UserPreferences;

uses(RefreshDatabase::class);

/**
 * Reset the package's internal preference caches.
 */
function resetPreferencesCache(): void
{
    $reflection = new ReflectionClass(UserPreferences::class);

    foreach (['preferencesCache', 'hasLoadedCache'] as $property) {
        $reflection->getProperty($property)->setValue([]);
    }

    Cache::flush();
}

it('can set and get a preference', function () {
    $user = User::factory()->create();
    Auth::login($user);

    UserPreferences::set('test_key', 'test_value');

    expect(UserPreferences::get('test_key'))->toBe('test_value');
});

it('loads preferences from the database', function () {
    $user = User::factory()->create();

    DB::table(config('user-preferences.database.table'))
        ->updateOrInsert(
            [config('user-preferences.database.primary_key') => $user->id],
            [config('user-preferences.database.column') => json_encode(['foo' => 'bar'])]
        );

    resetPreferencesCache();

    Auth::login($user);

    expect(UserPreferences::get('foo'))->toBe('bar');
});

it('uses default preferences when no data exists', function () {
    $user = User::factory()->create();
    Auth::login($user);

    resetPreferencesCache();

    DB::table(config('user-preferences.database.table'))
        ->where(config('user-preferences.database.primary_key'), $user->id)
        ->delete();

    expect(UserPreferences::get('some_default_key'))
        ->toBe(config('user-preferences.defaults.some_default_key'));
});

it('saves default preferences to the database', function () {
    $user = User::factory()->create();
    Auth::login($user);

    resetPreferencesCache();

    UserPreferences::setDefaultPreferences();

    $row = DB::table(config('user-preferences.database.table'))
        ->where(config('user-preferences.database.primary_key'), $user->id)
        ->first();

    expect($row)->not->toBeNull();

    $storedPreferences = json_decode(
        $row->{config('user-preferences.database.column')},
        true
    );

    expect($storedPreferences)->toEqual(config('user-preferences.defaults'));
});

it('throws an exception when setting an invalid preference type', function () {
    $user = User::factory()->create();
    Auth::login($user);

    config()->set('user-preferences.defaults.test_default_key', 'default_string');

    UserPreferences::set('test_default_key', 123);
})->throws(
    InvalidArgumentException::class,
    'The expected type is "string"! "integer" was given.'
);

it('restores the default value when resetting a preference', function () {
    $user = User::factory()->create();
    Auth::login($user);

    config()->set('user-preferences.defaults.reset_key', 'default_value');

    UserPreferences::set('reset_key', 'custom_value');

    expect(UserPreferences::get('reset_key'))->toBe('custom_value');

    $result = UserPreferences::reset('reset_key');

    expect($result)->toBeTrue()
        ->and(UserPreferences::get('reset_key'))->toBe('default_value');
});

it('returns false when a preference is not set', function () {
    $value = UserPreferences::has('key_not_set');

    expect($value)->toBeBool()->toBeFalse();
});

it('returns true when a preference is set', function () {
    UserPreferences::set('key_set', 'some_value');

    $value = UserPreferences::has('key_set');

    expect($value)->toBeBool()->toBeTrue();
});

it('returns all preferences', function () {
    $user = User::factory()->create();
    Auth::login($user);

    UserPreferences::set('key1', 'value1');
    UserPreferences::set('key2', 'value2');

    $allPreferences = UserPreferences::all();

    expect($allPreferences)->toBeArray()
        ->and($allPreferences['key1'])->toBe('value1')
        ->and($allPreferences['key2'])->toBe('value2');
});

it('returns default preferences when no data exists', function () {
    $user = User::factory()->create();
    Auth::login($user);

    config()->set('user-preferences.defaults.test_key1', 'value1');
    config()->set('user-preferences.defaults.test_key2', 'value2');

    resetPreferencesCache();

    DB::table(config('user-preferences.database.table'))
        ->where(config('user-preferences.database.primary_key'), $user->id)
        ->delete();

    $allPreferences = UserPreferences::all();

    expect($allPreferences)->toBeArray()
        ->and($allPreferences['test_key1'])->toBe('value1')
        ->and($allPreferences['test_key2'])->toBe('value2');
});

it('applies defaults when the database preferences column is null', function () {
    $user = User::factory()->create();
    Auth::login($user);

    resetPreferencesCache();

    DB::table(config('user-preferences.database.table'))
        ->where(config('user-preferences.database.primary_key'), $user->id)
        ->update([config('user-preferences.database.column') => null]);

    $defaults = config('user-preferences.defaults');

    expect(UserPreferences::all())->toBeArray()->toEqual($defaults);
});

describe('ForUser methods', function () {
    it('can set and get preferences for specific users', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserPreferences::setForUser('user_key', 'value1', $user1);
        UserPreferences::setForUser('user_key', 'value2', $user2);

        expect(UserPreferences::getForUser('user_key', $user1))->toBe('value1')
            ->and(UserPreferences::getForUser('user_key', $user2))->toBe('value2');
    });

    it('checks whether a preference exists for a specific user', function () {
        $user = User::factory()->create();

        expect(UserPreferences::hasForUser('missing_key', $user))->toBeFalse();

        UserPreferences::setForUser('exists_key', 'value', $user);

        expect(UserPreferences::hasForUser('exists_key', $user))->toBeTrue();
    });

    it('does not change the authenticated user when setting another user preference', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Auth::login($user1);

        expect(Auth::id())->toBe($user1->id);

        UserPreferences::setForUser('some_key', 'value_for_user2', $user2);

        expect(Auth::id())->toBe($user1->id)
            ->and(UserPreferences::getForUser('some_key', $user2))
            ->toBe('value_for_user2');
    });

    it('resets a preference for a specific user', function () {
        $user = User::factory()->create();

        UserPreferences::setForUser('reset_key', 'custom_value', $user);

        expect(UserPreferences::getForUser('reset_key', $user))
            ->toBe('custom_value');

        UserPreferences::resetForUser('reset_key', $user);

        expect(UserPreferences::getForUser('reset_key', $user))
            ->toBe(config('user-preferences.defaults.reset_key', null));
    });
});
