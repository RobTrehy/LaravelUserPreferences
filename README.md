# Laravel User Preferences
## A package for Laravel to store user preferences for your project.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/robtrehy/laravel-user-preferences.svg?style=flat-square)](https://packagist.org/packages/robtrehy/laravel-user-preferences)
[![GitHub Tests Action Status](https://github.com/RobTrehy/LaravelUserPreferences/actions/workflows/run-tests.yml/badge.svg)](https://github.com/RobTrehy/LaravelUserPreferences/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://github.com/RobTrehy/LaravelUserPreferences/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/RobTrehy/LaravelUserPreferences/actions/workflows/fix-php-code-style-issues.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/robtrehy/laravel-user-preferences.svg?style=flat-square)](https://packagist.org/packages/robtrehy/laravel-user-preferences)


This is a package for Laravel that can be used to store and access preferences of the currently authenticated user.
The preferences are stored as JSON in a single database column. The default configuration stores this alongside the user record in 
the `users` table.

## Installation

You can install the package via composer:

```bash
composer require robtrehy/laravel-user-preferences
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-user-preferences-config"
```


You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-user-preferences-migrations"
php artisan migrate
```

This will add the column defined in your configuration file to the table defined in your configuration file.

## Configuration
Open `config/user-preferences.php` to adjust the package's configuration. 

Set `table`, `column`, and `primary_key` to match your requirements. `primary_key` should be the users id.

Laravel User Preferences uses the Laravel Cache driver to reduce the number of queries on your database. By default Laravel caches using the `file` driver. If you wish to disable this, you can use the `null` driver.
The cache key supplied by Laravel User Preferences adds a prefix and suffix to the user's `id`. You can supply your own prefix and suffix by changing the `cache.prefix` and `cache.suffix` configuration values.

In the `defaults` array you can set your default values for user preferences.

#### Example configuration
```
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
Use this method to set a preference for the **currently authenticated user**:
```php
UserPreferences::set(string $setting, $value);
```

### Get a preference
Get the value of a preference for the **currently authenticated user**:
```php
UserPreferences::get(string $setting);
```

### Reset a preference
Reset a single preference for the **currently authenticated user**:
```php
UserPreferences::reset(string $setting);
```

### Reset all default preferences
Reset all default preferences for the **currently authenticated user**:
```php
UserPreferences::setDefaultPreferences();
```

### Get all preferences
Get all preferences for the **currently authenticated user**:
```php
UserPreferences::all();
```

### Check if a preference exists
Check if the **currently authenticated user** has a specific preference:
```php
UserPreferences::has(string $setting);
```

### Save a preference
All preferences are saved automatically when `UserPreferences::set()` is called.

---

## Arbitrary User Methods

You can now work with preferences for **any user instance or ID**, not just the currently authenticated user.

### Get a preference for a specific user
```php
UserPreferences::getForUser(string $setting, User|int $user);
```
- `$user` can be a `User` model instance or a user ID.  
- Returns the preference value if set, otherwise the default.

### Set a preference for a specific user
```php
UserPreferences::setForUser(string $setting, $value, User|int $user);
```
- `$user` can be a `User` model instance or a user ID.  
- Saves the preference for that user without affecting the currently authenticated user.

### Reset a preference for a specific user
```php
UserPreferences::resetForUser(string $setting, User|int $user);
```
- Returns `true` if the default was restored, `false` if the preference was deleted.

### Check if a specific user has a preference
```php
UserPreferences::hasForUser(string $setting, User|int $user);
```
- Returns `true` if a value exists, `false` otherwise.

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing
Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities
Please review our [security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License
This Laravel package is free software distributed under the terms of the MIT license.
See [LICENSE](LICENSE)
