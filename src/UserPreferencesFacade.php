<?php

namespace RobTrehy\LaravelUserPreferences;

use Illuminate\Support\Facades\Facade;

class UserPreferencesFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'user-preferences';
    }
}