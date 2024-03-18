<?php

namespace RobTrehy\LaravelUserPreferences\Factories;

use Orchestra\Testbench\Factories\UserFactory as TestbenchUserFactory;
use RobTrehy\LaravelUserPreferences\Tests\Models\User;

class UserFactory extends TestbenchUserFactory
{
    protected $model = User::class;

    /**
     * Define the model's default state
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => \Illuminate\Support\Str::random(10),
        ];
    }
}
