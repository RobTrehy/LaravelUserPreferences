<?php

namespace RobTrehy\LaravelUserPreferences\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RobTrehy\LaravelUserPreferences\UserPreferences
 */
class UserPreferences extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RobTrehy\LaravelUserPreferences\UserPreferences::class;
    }
}
