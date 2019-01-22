# Laravel User Preferences

This is a package for Laravel 5, that can be used to store and access preferences of the currently authenticated user.
The preferences are stored as JSON in a single database column. The default stores this alongside the user record in 
the `users` table.

## Installation
1. Run `composer require robtrehy/laravel-user-preferences` to include this in your project.
2. Add the UserPreferences alias to your `config\app.php` file:
    ```PHP
    'UserPreferences' => \RobTrehy\LaravelUserPreferences\UserPreferences::class,
    ```
3. Publish the config file with the following command
    ```
    php artisan vendor:publish --provider="RobTrehy\LaravelUserPreferences\UserPreferencesServiceProvider" --tag="config"
    ```
4. Modify the published configuration file to your requirements. The file is located at `config/user-preferences.php`.
5. Add the `preferences` column to the database. If you wish to add this to the `users` table, a migration file is 
included, just run the following command
    ```
    php artisan vendor:publish --provider="RobTrehy\LaravelUserPreferences\UserPreferencesServiceProvider" --tag="migrations" && php artisan migrate
    ```
    
## Configuration
Open `config/user-preferences.php` to adjust the packages configuration. 

If this file doesn't exist, run 
`php artisan vendor:public --provider="RobTrehy\LaravelUserPreferences\UserPreferencesServiceProvider" --tag="config"` 
to create the default configuration file.

Set `table`, `column`, and `primary_key` to match your requirements. `primary_key` should be the users id.

In the `defaults` array you can set your default values for user preferences.

#### Example configuration
```PHP
    'database' => [
        'table' => 'users',
        'column' => 'preferences',
        'primary_key' => 'id'
    ],
    'defaults' => [
        'theme' => 'blue',
        'show_welcome' => true
    ]
```

## Usage
Include LaravelUserPreferences into your controllers with
``` PHP
use RobTrehy\LaravelUserPreferences\UserPreferences
```
You can then use `UserPreferences` class to access the methods in this package.

### Set a preference
Use this method to set a preference for the currently authenticated user
```PHP
UserPreferences::set(string [setting], [value]);
```
If a default preference value is set in the config file, the new value must match the type of the default value. 

If no default value exists, any value type can be saved. If the default value type is not matched 
`UserPreferences::save()` will return an `InvalidArgumentException`.

### Reset all default preferences
Use this method to reset a user to the default preferences found in the config file.
```PHP
UserPreferences::setDefaultPreferences();
```
> Note: This will not adjust user preferences that do not contain a default value in the config file.

### Reset a specific default preference
Use this method to reset a single preference to the default value found in your config file, if it exists.
If no default value is set in the config file, the preference will be removed from the user record.
```PHP
UserPreferences::reset(string [setting]);
```
This method will return `true` if a default value was set from the config file. 
If no default value was found, this method will return `false`

### Get a preference
Use this method to get the value of a user preference.
```PHP
UserPreferences::get(string [setting]);
```

### Get all preferences
Use this method to get all of the user preferences
```PHP
UserPreferences::all()
```

### Check if a user has a specific preference
To check if a user has a specific preference set, you can call
```PHP
UserPreferences::has(string [setting]);
```
This will return `true` if a value was found, `false` if not.

### Save a preference
All preferences are saved automatically when `UserPreferences::set();` is called.

## License
This Laravel package is free software distributed under the terms of the MIT license.
See [LICENSE](LICENSE)

