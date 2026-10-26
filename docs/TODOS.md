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
- [x] Scopes and write methods didn't accept a Collection. Worse than a missing feature: outside `strict_types` PHP cast it to its JSON, so another user's tags passed as a Collection skipped `TagNotOwnedException` and became a tag name. They now take any iterable, read once; `string|iterable|Model` chosen so a model passed alone is caught too (Mujahid, 2026-10-26).
- [x] Document UUID/ULID setups: the stubs use `foreignId('user_id')->constrained()` (assumes a bigint `users` table, ignores `user_model`) and `morphs()` (bigint taggable ids). README "UUID and ULID Keys": `foreignIdFor(config('user-tags.user_model'), 'user_id')` and `uuidMorphs()`/`ulidMorphs()`; docs only, the stub stays as is (2026-10-26).
- Parked until someone asks: events, tag metadata (colors), usage stats.

## Test app
- `~/Herd/laravel-tags-project` (not a git repo) installs the package from Packagist and holds 27 use-case tests: `php artisan test --compact`. For MySQL or Postgres, prefix `DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=user_tags_usecases DB_USERNAME=root DB_PASSWORD=` (Postgres: `pgsql`, port 5432); both databases exist on Herd's services.

## Smaller issues
- [x] README config section was missing the `tag_model` key (2026-10-26).
- [x] With `tag_model` set to a subclass, a base `UserTag` instance skipped the ownership check and became a tag named after its JSON, not a TypeError: models cast to string outside strict_types. Fixed on `read-side-features` by checking against the base class (2026-10-26).
- [x] Any other object in a tag array (say `attachUserTag([$post])`) was cast to its JSON and became a tag name. Values that cast to JSON (`Jsonable`), nested lists and `null` now throw `InvalidArgumentException`, alone or in a list; numbers and `Stringable` values stay tag names (2026-10-26).
- [ ] `attachUserTag(['new name', $othersTag])` creates `new name` in the caller's tags before the `TagNotOwnedException`, so an unattached tag is left behind. Only the caller's own tags; `syncUserTags()` rolls it back in its transaction. Checking every instance's owner before creating names would close it.
- [x] On MySQL/MariaDB, `résumé`/`resume`, `straße`/`strasse` and `🔥`/`🍕` merged into one tag (slug compared by collation). Fixed with a binary slug collation plus an upgrade migration in the README, and a MySQL CI job (2026-10-26).
- [x] Another user's tag ID from a request was a 500: the README now shows mapping `TagNotOwnedException` to a 404.
- [x] Postgres: `enforces unique slug per user constraint` failed in the full suite only, because Postgres aborts the wrapping transaction after the expected error. The duplicate insert now runs in a savepoint, and a Postgres 18 CI job runs the suite (2026-10-26).
- [x] MariaDB wasn't tested anywhere. A MariaDB 12.3 CI job now runs the suite on Laravel's `mariadb` driver, so the stub's `mariadb` branch runs; without the binary slug collation, four tests fail there (2026-10-26).
- Parked until someone asks (Mujahid, 2026-10-26):
  - `NoAuthenticatedUserException::render()` always returns 401 JSON, even for web requests. Only HTTP requests render it, it is still logged, and it fires only on a code bug, so the gain would be an error page instead of a JSON 401.
  - `findOrCreateUserTags()` runs one query per tag name. `firstOrCreate` already survives concurrent creates through `createOrFirst`; batching would have to solve that again, for no visible gain at 1-10 tags per call.
  - `tests/Fixtures/User.php` `newFactory()` fails PHPStan `return.type` (anonymous `Factory` vs `Factory<static>`). Harmless while the fixture stays out of the analysed paths.
