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
    ],
    /**
     * When preference is set, the provided string will be used
     * as the key by default.
     * 
     * With this option enabled the key string will be exploded 
     * by the character in the second option.
     * 
     * E.g "notification.email" = {value} would become 
     * ["notification" => ["email" => {value}]]
     */
    'explode-key-strings' => false,
    'explode-key-character' => '.',
];
