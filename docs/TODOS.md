# TODOS

## Release
- [x] Version: 1.2.0, not 1.1.1, because the slug change needs existing rows re-slugged (Mujahid, 2026-10-26).
- [x] Released v1.2.0 on 2026-10-26: PR #5 rebase-merged, tag and GitHub Release published, Packagist lists it at `6a44e9c`.

## Features (the package is not on current-priorities)
- [x] Read-side features, built 2026-10-26 on branch `read-side-features`: `userTagsFor()`, `UserTag::forUser()` and `containing()`, the `OwnsUserTags` trait (`ownedUserTags()`, not `userTags()`, to avoid a trait collision), and query scopes that accept `UserTag` instances. Design choices are in each commit message.
- [ ] Review and merge PR #7, then release as 1.3.0 (additive, so minor). The subclass ownership fix rides along rather than a 1.2.1 patch (Mujahid, 2026-10-26).
- [ ] Scopes and write methods don't accept a Collection (spatie/laravel-tags takes `ArrayAccess`); callers pass `->all()` for now.
- [ ] Document UUID/ULID setups: the stubs use `foreignId('user_id')->constrained()` (assumes a bigint `users` table, ignores `user_model`) and `morphs()` (bigint taggable ids).
- Parked until someone asks: events, tag metadata (colors), usage stats.

## Smaller issues
- [ ] README config section is missing the `tag_model` key.
- [ ] `NoAuthenticatedUserException::render()` always returns 401 JSON, even for web requests; the usual cause is a job or command with no user passed, which is a developer error.
- [x] With `tag_model` set to a subclass, a base `UserTag` instance skipped the ownership check and became a tag named after its JSON, not a TypeError: models cast to string outside strict_types. Fixed on `read-side-features` by checking against the base class (2026-10-26).
- [ ] Any other object in a tag array (say `attachUserTag([$post])`) is still cast to its JSON and becomes a tag name. Rejecting values that are neither strings nor `UserTag` would close it; developer error, not an isolation issue.
- [ ] `findOrCreateUserTags()` runs one query per tag name; fine at current scale.
- [ ] `tests/Fixtures/User.php` `newFactory()` fails PHPStan `return.type` (anonymous `Factory` vs `Factory<static>`). Harmless while the fixture stays out of the analysed paths.
