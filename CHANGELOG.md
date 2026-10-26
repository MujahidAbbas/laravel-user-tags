# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Support for Laravel 13
- Support for PHP 8.4 and 8.5

### Removed

- Support for Laravel 10 and 11 (both past end of support; use v1.0.0)
- Support for PHP 8.1

## [1.0.0] - 2026-01-27

### Added

- `HasUserTags` trait for Eloquent models
- `UserTag` model with auto-slug generation
- `attachUserTag()` method for adding tags (creates if not exists)
- `detachUserTag()` method for removing tags
- `syncUserTags()` method for replacing user's tags on a model
- `userTags()` relationship for accessing all tags
- `withAnyUserTags()` query scope for filtering by any matching tags
- `withAllUserTags()` query scope for filtering by all matching tags
- `withoutUserTags()` query scope for excluding models with specific tags
- User-scoped tagging (each user has their own tag namespace)
- Configurable table names via config file
- Configurable tag model via config file
- Support for PHP 8.1, 8.2, and 8.3
- Support for Laravel 10, 11, and 12
- Publishable migrations and config
- `NoAuthenticatedUserException` for missing authentication context

[Unreleased]: https://github.com/mujahidabbas/laravel-user-tags/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/mujahidabbas/laravel-user-tags/releases/tag/v1.0.0
