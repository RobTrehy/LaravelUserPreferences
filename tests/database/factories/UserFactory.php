<?php

namespace RobTrehy\LaravelUserPreferences\Tests\Database\Factories;

use Illuminate\Support\Str;
use Orchestra\Testbench\Factories\UserFactory as TestbenchUserFactory;
use RobTrehy\LaravelUserPreferences\Tests\Models\User;

class UserFactory extends TestbenchUserFactory
{
    protected $model = User::class;

    /**
     * Define the model's default state
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
