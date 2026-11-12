# Changelog

All notable changes to `robtrehy/laravel-user-preferences` will be documented in this file.

## 5.0.0 - 2026-11-12

Version 5 modernises the package using Spatie’s Laravel package skeleton and improves the reliability of preference storage and caching.

### Requirements

- PHP 8.4 or later.
- Laravel 12 or 13.

Support for PHP 8.2, PHP 8.3, and Laravel 11 has been dropped.

### Behaviour changes

- Calls without an explicit user ID now require authentication and throw `Illuminate\Auth\AuthenticationException` otherwise.
- Explicit user IDs remain usable without authentication. Writes to missing users throw `Illuminate\Database\RecordNotFoundException`.
- `setDefaultPreferences()` now replaces all preferences with configured defaults, removing custom keys.
- Falsy defaults—including `false`, `0`, empty strings, empty arrays, and `null`—participate in type validation and reset handling.
- Invalid JSON and JSON values that are not objects fall back to configured defaults.
- JSON encoding failures throw rather than silently attempting to save invalid data.

### Reliability and performance

- Empty preference results are cached.
- Local preferences are cleared at request, job, and transaction boundaries.
- Transactional reads bypass caches, and shared-cache invalidation waits for commit.
- Updates use fresh database state and row locking to preserve concurrent changes to other keys.
- Shared-cache population is coordinated with invalidation to prevent stale values from being cached after writes.
- Expanded regression and concurrency test coverage.

### Cache requirements

Shared caching now requires a driver supporting Laravel atomic locks. Multiple servers must share the same cache and lock storage. See the README for supported drivers and lock-timeout behaviour.

Nested-key configuration remains reserved for future use.

### Deployment note

The preference cache format has changed. Stop processes running the previous version, deploy the update, clear existing preference cache entries, then restart workers. Avoid running old and new versions against the same preference cache. Clear preference caches again if rolling back.

Laravel’s `php artisan cache:clear` clears the entire default cache store, including unrelated application cache entries. Alternatively, remove only preference entries using your configured prefix and suffix.

## 4.2.1 - 2026-07-21
- Cache preference column values as scalars instead of Collections (Laravel 13 `serializable_classes` compatibility)

## 4.2.0 - 2026-07-21
- Added support for Laravel 13

## 4.1.3 - 2025-11-10
- Hotfix: Ensure when the database column is null, `UserPreferences::all()` applies the defaults and does not trigger a json_decode(null) warning

## 4.1.2 - 2025-11-10
- Hotfix: Ensure `UserPreferences::all()` method returns an array for backwards compatibility

## 4.1.1 - 2025-11-09
- Fix: Avoid deprecation warning in PHP 8.1+ when preferences column is null (#20)

## 4.1.0 - 2025-11-09
- Added methods for working with preferences for any user instance or ID:
  - `getForUser()`
  - `setForUser()`
  - `resetForUser()`
  - `hasForUser()`
- Achieved 100% test coverage for `UserPreferences` class.
- Updated internal caching to support per-user caching (`$preferencesCache` and `$hasLoadedCache`).
- Minor docblock improvements.

## 4.0.0 - 2025-08-01
- Added support for Laravel 12
- Removed support for Laravel 9

## 3.1.0 - 2024-03-18
- Added support for Laravel 11
- Added support for Laravel 10

## 3.0.0 - 2023-01-15
- Added support for Laravel 9

## 2.1.1 - 2022-03-30
- Security Updates
- Fix: avoid passing null to json_decode in PHP 8.1 [Reex11](https://github.com/Reex11)

## 2.1.0 - 2021-08-02
- Added Cache support (thanks to [fefo-p](https://github.com/fefo-p) and [theVannu](https://github.com/theVannu))

## 2.0.1 - 2021-06-30
- Security Updates

## 2.0.0 - 2021-04-06
- Added support for Laravel 8
- Added code tests

## 1.0.1 - 2019-03-27
- Fixed issue of incorrect column name
- Fixed error in readme

## 1.0.0 - 2019-01-22
- initial release
