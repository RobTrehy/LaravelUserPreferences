<?php

namespace RobTrehy\LaravelUserPreferences\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use RobTrehy\LaravelUserPreferences\Tests\Models\User;
use RobTrehy\LaravelUserPreferences\UserPreferences;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testCanSetAndGetAPreference()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Auth::login($user);

        UserPreferences::set('test_key', 'test_value');
        $value = UserPreferences::get('test_key');
        
        $this->assertEquals('test_value', $value);
    }

    /** @test */
    public function testCanCheckIfPreferenceIsNotSet()
    {
        $value = UserPreferences::has('key_not_set');

        $this->assertIsBool($value);
        $this->assertFalse($value);
    }

    /** @test */
    public function testCanCheckIfPreferenceIsSet()
    {
        UserPreferences::set('key_set', 'some_value');
        $value = UserPreferences::has('key_set');

        $this->assertIsBool($value);
        $this->assertTrue($value);
    }
}
