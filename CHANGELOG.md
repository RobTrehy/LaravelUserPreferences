# Changelog

All notable changes to `robtrehy/laravel-user-preferences` will be documented in this file.

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
