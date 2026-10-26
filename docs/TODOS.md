# TODOS

## Release
- [x] Version: 1.2.0, not 1.1.1, because the slug change needs existing rows re-slugged (Mujahid, 2026-10-26).
- [x] Released v1.2.0 on 2026-10-26: PR #5 rebase-merged, tag and GitHub Release published, Packagist lists it at `6a44e9c`.
- [x] Version: 1.3.0, minor because everything is additive; the subclass ownership fix rides along rather than a 1.2.1 patch (Mujahid, 2026-10-26).
- [x] Released v1.3.0 on 2026-10-26: PR #9 rebase-merged, tag and GitHub Release published, Packagist lists it at `346a8c1`.
- [x] Version: 1.3.1, a patch: the MySQL collation fix changes no API; existing installs opt in with the README migration (Mujahid, 2026-10-26).
- [x] Released v1.3.1 on 2026-10-26: PR #12 rebase-merged, tag and GitHub Release published, Packagist lists it at `6d79b70`. The test app runs it: 27 use-case tests pass on SQLite, MySQL and Postgres.

## Features (the package is not on current-priorities)
- [x] Read-side features, built 2026-10-26 on branch `read-side-features`: `userTagsFor()`, `UserTag::forUser()` and `containing()`, the `OwnsUserTags` trait (`ownedUserTags()`, not `userTags()`, to avoid a trait collision), and query scopes that accept `UserTag` instances. Design choices are in each commit message.
- [x] PR #7 rebase-merged on 2026-10-26; main at `38ceff3`, CI green on the branch head before merge.
- [ ] Scopes and write methods don't accept a Collection (spatie/laravel-tags takes `ArrayAccess`); callers pass `->all()` for now.
- [ ] Document UUID/ULID setups: the stubs use `foreignId('user_id')->constrained()` (assumes a bigint `users` table, ignores `user_model`) and `morphs()` (bigint taggable ids).
- Parked until someone asks: events, tag metadata (colors), usage stats.

## Test app
- `~/Herd/laravel-tags-project` (not a git repo) installs the package from Packagist and holds 27 use-case tests: `php artisan test --compact`. For MySQL or Postgres, prefix `DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=user_tags_usecases DB_USERNAME=root DB_PASSWORD=` (Postgres: `pgsql`, port 5432); both databases exist on Herd's services.

## Smaller issues
- [ ] README config section is missing the `tag_model` key.
- [ ] `NoAuthenticatedUserException::render()` always returns 401 JSON, even for web requests; the usual cause is a job or command with no user passed, which is a developer error.
- [x] With `tag_model` set to a subclass, a base `UserTag` instance skipped the ownership check and became a tag named after its JSON, not a TypeError: models cast to string outside strict_types. Fixed on `read-side-features` by checking against the base class (2026-10-26).
- [ ] Any other object in a tag array (say `attachUserTag([$post])`) is still cast to its JSON and becomes a tag name. Rejecting values that are neither strings nor `UserTag` would close it; developer error, not an isolation issue.
- [ ] `findOrCreateUserTags()` runs one query per tag name; fine at current scale.
- [x] On MySQL/MariaDB, `résumé`/`resume`, `straße`/`strasse` and `🔥`/`🍕` merged into one tag (slug compared by collation). Fixed with a binary slug collation plus an upgrade migration in the README, and a MySQL CI job (2026-10-26).
- [x] Another user's tag ID from a request was a 500: the README now shows mapping `TagNotOwnedException` to a 404.
- [ ] Postgres: `enforces unique slug per user constraint` fails in the full suite only, because Postgres aborts the wrapping transaction after the expected error. Wrap that insert in a savepoint before adding a Postgres CI job.
- [ ] MariaDB isn't tested anywhere; the stub's `mariadb` branch only runs for apps on Laravel's `mariadb` driver.
- [ ] `tests/Fixtures/User.php` `newFactory()` fails PHPStan `return.type` (anonymous `Factory` vs `Factory<static>`). Harmless while the fixture stays out of the analysed paths.
