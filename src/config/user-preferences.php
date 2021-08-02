<?php

return [
    'database' => [
        'table' => 'users',
        'column' => 'preferences',
        'primary_key' => 'id'
    ],
    'cache' => [
        'prefix' => 'user-',
        'suffix' => '-preferences',
    ],
    'defaults' => [
        // 'Default Preferences go here
        // 'key' => 'value'
    ]
];
