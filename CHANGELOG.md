# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- On MySQL and MariaDB, tag names that differ only by accents, `ß`/`ss` or emoji (`résumé` and `resume`, `🔥` and `🍕`) were merged into one tag, and `containing()` ignored accents, because `slug` used the connection's collation. The migration now gives `slug` a binary collation. Existing installs need the migration in "Upgrading from 1.3.0" in the README

## [1.3.0] - 2026-10-26

### Added

- `userTagsFor()` relation for one user's tags on a model; `with('userTagsFor')` eager loads the authenticated user's tags
- `OwnsUserTags` trait for the User model, with an `ownedUserTags()` relationship
- `UserTag::forUser()` scope, and `containing()` for searching tag names, for example for autocomplete
- `withAnyUserTags()`, `withAllUserTags()` and `withoutUserTags()` accept `UserTag` instances; another user's instance throws `TagNotOwnedException`

### Fixed

- With `tag_model` set to a subclass, passing a base `UserTag` instance (as `UserTag::find()` returns) skipped the ownership check and created a tag named after the instance's JSON, which could copy another user's tag name into the caller's tags. It is now treated as the tag it is, and another user's instance throws `TagNotOwnedException`

## [1.2.0] - 2026-10-26

### Fixed

- Passing another user's `UserTag` instance to `attachUserTag()`, `detachUserTag()` or `syncUserTags()` now throws `TagNotOwnedException` instead of acting on that user's tag
- `syncUserTags()` no longer leaves a user's tags detached when the attach fails: it runs in a transaction, and names that resolve to the same tag are attached once
- `withAllUserTags()` no longer returns nothing when the same name is given twice in different case
- Deleting a tagged model now removes its tag attachments; soft-deleted models keep them until force deleted
- Renaming a tag now updates its slug, so it is found by its new name
- Blank tag names are skipped instead of creating an empty tag

### Changed

- Tags are matched on the lowercased, whitespace-squished name instead of `Str::slug()`. `C++` and `C#` are now separate tags, and non-Latin and emoji names no longer collapse into one tag. Existing rows need a one-time re-slug, see "Upgrading from 1.1.0" in the README
- The test suite now runs the shipped migration stubs instead of copies of them

## [1.1.0] - 2026-10-26

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

[Unreleased]: https://github.com/mujahidabbas/laravel-user-tags/compare/v1.3.0...HEAD
[1.3.0]: https://github.com/mujahidabbas/laravel-user-tags/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/mujahidabbas/laravel-user-tags/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/mujahidabbas/laravel-user-tags/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/mujahidabbas/laravel-user-tags/releases/tag/v1.0.0
