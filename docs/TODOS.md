# TODOS

## Release
- [x] Version: 1.2.0, not 1.1.1, because the slug change needs existing rows re-slugged (Mujahid, 2026-10-26).
- [ ] Merge the `fix/isolation-and-tag-matching` PR, tag v1.2.0, check Packagist picked it up.

## Features (not started; the package is not on current-priorities)
- [ ] `userTagsFor($user)` relation, eager-loadable, so callers stop loading every user's tags and filtering in PHP (the README still shows `$article->userTags->where('user_id', ...)`).
- [ ] `UserTag::forUser($user)` and `containing($term)` scopes for tag lists and autocomplete.
- [ ] Trait for the User model: `$user->userTags()`.
- [ ] Query scopes accept `UserTag` instances, like attach/detach/sync already do.
- [ ] Document UUID/ULID setups: the stubs use `foreignId('user_id')->constrained()` (assumes a bigint `users` table, ignores `user_model`) and `morphs()` (bigint taggable ids).
- Parked until someone asks: events, tag metadata (colors), usage stats.

## Smaller issues
- [ ] README config section is missing the `tag_model` key.
- [ ] `NoAuthenticatedUserException::render()` always returns 401 JSON, even for web requests; the usual cause is a job or command with no user passed, which is a developer error.
- [ ] From reading the code, not run: with `tag_model` set to a subclass, passing a base `UserTag` instance fails the `instanceof $tagClass` check and reaches `slugFromName()` as an object (TypeError).
- [ ] `findOrCreateUserTags()` runs one query per tag name; fine at current scale.
