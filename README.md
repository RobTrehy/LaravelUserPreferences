# Laravel User Preferences
![](https://img.shields.io/github/actions/workflow/status/RobTrehy/LaravelUserPreferences/tests.yml?branch=master&style=flat-square)
![](https://img.shields.io/github/license/RobTrehy/LaravelUserPreferences?style=flat-square)
![](https://img.shields.io/github/languages/code-size/RobTrehy/LaravelUserPreferences?style=flat-square)
![](https://img.shields.io/packagist/v/robtrehy/laravel-user-preferences?style=flat-square)
![](https://img.shields.io/packagist/dt/robtrehy/laravel-user-preferences?style=flat-square)

This is a package for Laravel that can be used to store and access preferences of the currently authenticated user.
The preferences are stored as JSON in a single database column. The default configuration stores this alongside the user record in 
the `users` table.

## Installation
1. Run `composer require robtrehy/laravel-user-preferences` to include this in your project.
2. Publish the config file with the following command
    ```
    php artisan vendor:publish --provider="RobTrehy\LaravelUserPreferences\UserPreferencesServiceProvider" --tag="config"
    ```
4. Modify the published configuration file to your requirements. The file is located at `config/user-preferences.php`.
5. Add the `preferences` column to the database. A migration file is included, just run the following command
    ```
    php artisan vendor:publish --provider="RobTrehy\LaravelUserPreferences\UserPreferencesServiceProvider" --tag="migrations" && php artisan migrate
    ```
    This will add the column defined in your configuration file to the table defined in your configuration file.
    
## Configuration
Open `config/user-preferences.php` to adjust the packages configuration. 

If this file doesn't exist, run 
`php artisan vendor:public --provider="RobTrehy\LaravelUserPreferences\UserPreferencesServiceProvider" --tag="config"` 
to create the default configuration file.

Set `table`, `column`, and `primary_key` to match your requirements. `primary_key` should be the users id.

Laravel User Preferences uses the Laravel Cache driver to reduce the number of queries on your database. By default Laravel Caches using the `file` driver. If you wish to disable this, you can use the `null` driver.
The cache key supplied by Laravel User Preferences adds a prefix and suffix to the user's `id`. You can supply your own prefix and suffix by changing the `cache.prefix` and `cache.suffix` configuration values.

In the `defaults` array you can set your default values for user preferences.

#### Example configuration
```PHP
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
        'theme' => 'blue',
        'show_welcome' => true
    ]
```

## Usage

### Set a preference
Use this method to set a preference for the **currently authenticated user**
```PHP
UserPreferences::set(string [setting], [value]);
```
If a default preference value is set in the config file, the new value must match the type of the default value. 

If no default value exists, any value type can be saved. If the default value type is not matched 
`UserPreferences::save()` will return an `InvalidArgumentException`.

### Reset all default preferences
Use this method to reset the **currently authenticated user** to the default preferences found in the config file.
```PHP
UserPreferences::setDefaultPreferences();
```
> Note: This will not adjust user preferences that do not contain a default value in the config file.

### Reset a specific default preference
Use this method to reset a single preference, for the **currently authenticated user**, to the default value found in your config file, if it exists.
If no default value is set in the config file, the preference will be removed from the **currently authenticated user**'s record.
```PHP
UserPreferences::reset(string [setting]);
```
This method will return `true` if a default value was set from the config file. 
If no default value was found, this method will return `false`

### Get a preference
Use this method to get the value of a preference for the **currently authenticated user**.
```PHP
UserPreferences::get(string [setting]);
```

### Get all preferences
Use this method to get all of the **currently authenticated user**'s preferences
```PHP
UserPreferences::all()
```

### Check if a user has a specific preference
To check if the **currently authenticated user** has a specific preference set, you can call
```PHP
UserPreferences::has(string [setting]);
```
This will return `true` if a value was found, `false` if not.

### Save a preference
All preferences are saved automatically when `UserPreferences::set();` is called.

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing
Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities
Please review our [security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License
This Laravel package is free software distributed under the terms of the MIT license.
See [LICENSE](LICENSE)

