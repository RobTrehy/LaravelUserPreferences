<?php

namespace RobTrehy\LaravelUserPreferences\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RobTrehy\LaravelUserPreferences\Tests\Models\User;
use RobTrehy\LaravelUserPreferences\UserPreferences;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function testCanSetAndGetAPreference()
    {
        $user = User::factory()->create();
        Auth::login($user);

        UserPreferences::set('test_key', 'test_value');
        $value = UserPreferences::get('test_key');

        $this->assertEquals('test_value', $value);
    }

    public function testGetPreferencesLoadsFromDatabase()
    {
        $user = User::factory()->create();

        $rawPreferences = ['foo' => 'bar'];
        DB::table(config('user-preferences.database.table'))
            ->updateOrInsert(
                [config('user-preferences.database.primary_key') => $user->id],
                [config('user-preferences.database.column') => json_encode($rawPreferences)]
            );

        $reflection = new \ReflectionClass(UserPreferences::class);
        $preferencesCacheProp = $reflection->getProperty('preferencesCache');
        $preferencesCacheProp->setAccessible(true);
        $preferencesCacheProp->setValue([]); // reset all users

        $hasLoadedCacheProp = $reflection->getProperty('hasLoadedCache');
        $hasLoadedCacheProp->setAccessible(true);
        $hasLoadedCacheProp->setValue([]); // reset all users
            
        Cache::flush();

        Auth::login($user);

        $value = UserPreferences::get('foo');

        $this->assertEquals('bar', $value);
    }
    
    public function testGetPreferencesUsesDefaultsWhenNoDataExists()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $reflection = new \ReflectionClass(UserPreferences::class);
        $preferencesCacheProp = $reflection->getProperty('preferencesCache');
        $preferencesCacheProp->setAccessible(true);
        $preferencesCacheProp->setValue([]); // reset all users

        $hasLoadedCacheProp = $reflection->getProperty('hasLoadedCache');
        $hasLoadedCacheProp->setAccessible(true);
        $hasLoadedCacheProp->setValue([]); // reset all users

        Cache::flush();

        DB::table(config('user-preferences.database.table'))
            ->where(config('user-preferences.database.primary_key'), $user->id)
            ->delete();

        $value = UserPreferences::get('some_default_key');

        $this->assertEquals(config('user-preferences.defaults.some_default_key'), $value);
    }

    public function testSetDefaultPreferencesSavesDefaultsToDatabase()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $reflection = new \ReflectionClass(UserPreferences::class);
        $preferencesCacheProp = $reflection->getProperty('preferencesCache');
        $preferencesCacheProp->setAccessible(true);
        $preferencesCacheProp->setValue([]); // reset all users

        $hasLoadedCacheProp = $reflection->getProperty('hasLoadedCache');
        $hasLoadedCacheProp->setAccessible(true);
        $hasLoadedCacheProp->setValue([]); // reset all users

        Cache::flush();

        UserPreferences::setDefaultPreferences();

        $row = DB::table(config('user-preferences.database.table'))
            ->where(config('user-preferences.database.primary_key'), $user->id)
            ->first();

        $this->assertNotNull($row);

        $storedPreferences = json_decode($row->{config('user-preferences.database.column')}, true);
        $this->assertEquals(config('user-preferences.defaults'), $storedPreferences);
    }

    public function testSetThrowsExceptionForInvalidType()
    {
        $user = User::factory()->create();
        Auth::login($user);

        config()->set('user-preferences.defaults.test_default_key', 'default_string');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The expected type is "string"! "integer" was given.');

        UserPreferences::set('test_default_key', 123);
    }

    public function testResetRestoresDefaultValue()
    {
        $user = User::factory()->create();
        Auth::login($user);

        config()->set('user-preferences.defaults.reset_key', 'default_value');

        UserPreferences::set('reset_key', 'custom_value');
        $this->assertEquals('custom_value', UserPreferences::get('reset_key'));

        $result = UserPreferences::reset('reset_key');

        $this->assertTrue($result);
        $this->assertEquals('default_value', UserPreferences::get('reset_key'));
    }

    public function testCanCheckIfPreferenceIsNotSet()
    {
        $value = UserPreferences::has('key_not_set');

        $this->assertIsBool($value);
        $this->assertFalse($value);
    }

    public function testCanCheckIfPreferenceIsSet()
    {
        UserPreferences::set('key_set', 'some_value');
        $value = UserPreferences::has('key_set');

        $this->assertIsBool($value);
        $this->assertTrue($value);
    }

    public function testAllReturnsAllPreferences()
    {
        $user = User::factory()->create();
        Auth::login($user);

        UserPreferences::set('key1', 'value1');
        UserPreferences::set('key2', 'value2');

        $allPreferences = UserPreferences::all();

        $this->assertIsObject($allPreferences);
        $this->assertEquals('value1', $allPreferences->key1);
        $this->assertEquals('value2', $allPreferences->key2);
    }

    // --------------------------------------------------------
    // Tests for the new "ForUser" methods
    // --------------------------------------------------------

    public function testCanSetAndGetPreferenceForSpecificUser()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserPreferences::setForUser('user_key', 'value1', $user1);
        UserPreferences::setForUser('user_key', 'value2', $user2);

        $this->assertEquals('value1', UserPreferences::getForUser('user_key', $user1));
        $this->assertEquals('value2', UserPreferences::getForUser('user_key', $user2));
    }

    public function testHasForUserWorksCorrectly()
    {
        $user = User::factory()->create();

        $this->assertFalse(UserPreferences::hasForUser('missing_key', $user));

        UserPreferences::setForUser('exists_key', 'value', $user);
        $this->assertTrue(UserPreferences::hasForUser('exists_key', $user));
    }

    public function testLoggedInUserIsNotChangedWhenUsingSetForUser()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Auth::login($user1);
        $this->assertEquals($user1->id, Auth::id());

        UserPreferences::setForUser('some_key', 'value_for_user2', $user2);

        $this->assertEquals($user1->id, Auth::id());

        $this->assertEquals('value_for_user2', UserPreferences::getForUser('some_key', $user2));
    }

    public function testResetForUserResetsPreference()
    {
        $user = User::factory()->create();

        UserPreferences::setForUser('reset_key', 'custom_value', $user);
        $this->assertEquals('custom_value', UserPreferences::getForUser('reset_key', $user));

        UserPreferences::resetForUser('reset_key', $user);
        $this->assertEquals(config('user-preferences.defaults.reset_key', null), UserPreferences::getForUser('reset_key', $user));
    }
}
