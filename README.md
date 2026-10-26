# Laravel User Tags

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mujahidabbas/laravel-user-tags.svg?style=flat-square)](https://packagist.org/packages/mujahidabbas/laravel-user-tags)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mujahidabbas/laravel-user-tags/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mujahidabbas/laravel-user-tags/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mujahidabbas/laravel-user-tags.svg?style=flat-square)](https://packagist.org/packages/mujahidabbas/laravel-user-tags)
[![License](https://img.shields.io/packagist/l/mujahidabbas/laravel-user-tags?style=flat-square)](LICENSE.md)

User-scoped tagging for Laravel Eloquent models. Each user has their own private set of tags that are invisible to and independent from other users' tags.

```php
// User A tags an article
$article->attachUserTag('important'); // Creates User A's "important" tag

// User B tags the same article
Auth::login($userB);
$article->attachUserTag('important'); // Creates User B's separate "important" tag

// Query User A's tagged articles (only sees their own tags)
Auth::login($userA);
$articles = Article::withAnyUserTags(['important'])->get();
```

## Why This Package?

If you've used [spatie/laravel-tags](https://github.com/spatie/laravel-tags) and wondered how to give each user their own private tag namespace, this package is for you.

**The problem:** Global tagging packages share tags across all users. User A's "important" tag is the same as User B's "important" tag.

**The solution:** This package isolates tags per user. Each user has their own private set of tags that are completely invisible to and independent from other users' tags.

**Use cases:**
- Personal CRM systems where users organize their own contacts
- Bookmark managers with private collections
- Note-taking apps with personal organization
- Any multi-user app where users need private categorization

## Requirements

- PHP 8.2 or higher (Laravel 13 needs PHP 8.3+)
- Laravel 12.x or 13.x

Using Laravel 10 or 11? Install v1.0.0: `composer require mujahidabbas/laravel-user-tags:^1.0`

## Installation

You can install the package via composer:

```bash
composer require mujahidabbas/laravel-user-tags
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="user-tags-migrations"
php artisan migrate
```

Optionally, you can publish the config file:

```bash
php artisan vendor:publish --tag="user-tags-config"
```

## Configuration

The published config file (`config/user-tags.php`) contains:

```php
return [
    // Your User model class
    'user_model' => 'App\\Models\\User',

    // Table names (customize if needed)
    'tables' => [
        'tags' => 'user_tags',
        'taggables' => 'user_taggables',
    ],
];
```

## Usage

### Preparing Your Model

Add the `HasUserTags` trait to any Eloquent model you want to tag:

```php
use Illuminate\Database\Eloquent\Model;
use MujahidAbbas\LaravelUserTags\Traits\HasUserTags;

class Article extends Model
{
    use HasUserTags;
}
```

### Attaching Tags

Use `attachUserTag()` to add tags to a model. Tags are automatically created if they don't exist:

```php
// Attach a single tag (uses authenticated user)
$article->attachUserTag('important');

// Attach multiple tags at once
$article->attachUserTag(['important', 'read-later', 'favorites']);

// Attach with a specific user (useful in queues, commands, etc.)
$article->attachUserTag('important', $user);
```

Tags are user-scoped, so User A's "important" tag is completely separate from User B's "important" tag.

Tag names are matched ignoring case and extra whitespace, so `Important`, `important` and ` important ` are the same tag. Punctuation and non-Latin characters are kept: `C++` and `C#` are separate tags, and so are `重要` and `🔥`. Blank names are skipped.

On MySQL and MariaDB this relies on the binary collation the migration gives the `slug` column. Installed before 1.3.1? See [Upgrading from 1.3.0](#upgrading-from-130).

### Detaching Tags

Use `detachUserTag()` to remove tags from a model. Non-existent tags are silently ignored:

```php
// Detach a single tag
$article->detachUserTag('important');

// Detach multiple tags
$article->detachUserTag(['important', 'read-later']);

// Detach with a specific user
$article->detachUserTag('important', $user);
```

Note: This only removes the association; the tag itself is not deleted.

### Syncing Tags

Use `syncUserTags()` to replace all of a user's tags on a model with a new set:

```php
// Replace all current user's tags with exactly these
$article->syncUserTags(['priority', 'work']);

// Sync with a specific user
$article->syncUserTags(['priority', 'work'], $user);
```

**Important:** `syncUserTags()` only affects the specified user's tags. Other users' tags on the same model remain untouched.

### Passing Tag Instances

`attachUserTag()`, `detachUserTag()` and `syncUserTags()` also accept `UserTag` instances, and so do the [query scopes](#querying-by-tags). An instance that belongs to a different user throws a `TagNotOwnedException`, so a tag ID taken from a request can't touch or reveal another user's tags:

```php
use MujahidAbbas\LaravelUserTags\Exceptions\TagNotOwnedException;

$tag = UserTag::findOrFail($request->input('tag_id'));

$article->attachUserTag($tag); // Throws TagNotOwnedException unless $tag is the current user's
$articles = Article::withAnyUserTags($tag)->get(); // Same check
```

Lists can be arrays or Collections, so several tag IDs from a request get the same check:

```php
$tags = UserTag::findMany($request->input('tag_ids'));

$article->syncUserTags($tags); // Throws TagNotOwnedException if any of them is another user's
```

Anything that is neither a tag name nor a `UserTag`, such as another model, a nested list or `null`, throws an `InvalidArgumentException`. Numbers and `Stringable` values such as `str('Work')` are tag names.

`TagNotOwnedException` has no HTTP response of its own, so an uncaught one is a 500. To answer as if the tag did not exist, map it to a 404 in `bootstrap/app.php`:

```php
use MujahidAbbas\LaravelUserTags\Exceptions\TagNotOwnedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->map(fn (TagNotOwnedException $e) => new NotFoundHttpException(previous: $e));
})
```

### Deleting Tagged Models

Deleting a model removes its tag attachments; the tags themselves stay. Models using `SoftDeletes` keep their attachments until they are force deleted, so a restore brings the tags back.

### Accessing Tags

The `userTags()` relationship returns all tags attached to a model (from all users):

```php
// Get all tags on this model, from every user
$tags = $article->userTags;

// Eager load tags
$articles = Article::with('userTags')->get();
```

Use `userTagsFor()` for one user's tags. It defaults to the authenticated user:

```php
// The authenticated user's tags on this model
$myTags = $article->userTagsFor;

// A specific user's tags
$tags = $article->userTagsFor($user)->get();

// Eager load the authenticated user's tags
$articles = Article::with('userTagsFor')->get();

foreach ($articles as $article) {
    $article->userTagsFor; // No extra query
}
```

Eager loading calls the relationship without arguments, so `with('userTagsFor')` always uses the authenticated user and throws a `NoAuthenticatedUserException` when there is none. To eager load another user's tags, for example in a queued job, constrain `userTags` instead:

```php
$articles = Article::with(['userTags' => fn ($query) => $query->forUser($user)])->get();

$article->userTags; // Only $user's tags
```

### Querying by Tags

Query models that have specific tags using scope methods:

```php
// Get posts with ANY of these tags (OR logic)
$posts = Post::withAnyUserTags(['important', 'urgent'])->get();

// Get posts with ALL of these tags (AND logic)
$posts = Post::withAllUserTags(['priority', 'review'])->get();

// Get posts WITHOUT specific tags
$posts = Post::withoutUserTags(['archived', 'spam'])->get();

// Chain scopes
$posts = Post::withAnyUserTags(['tech', 'science'])
    ->withoutUserTags(['archived'])
    ->get();

// With a specific user (useful in queues, commands)
$posts = Post::withAnyUserTags(['important'], $user)->get();

// With tag instances, alone or mixed with names
$posts = Post::withAllUserTags([$tag, 'urgent'])->get();
```

**Note:** Query scopes respect user isolation - User A's scope queries only consider User A's tags.

### Listing and Searching a User's Tags

Add the `OwnsUserTags` trait to your User model to get all of a user's tags, including ones not attached to anything:

```php
use MujahidAbbas\LaravelUserTags\Traits\OwnsUserTags;

class User extends Authenticatable
{
    use OwnsUserTags;
}

$tags = $user->ownedUserTags;

// Create a tag without attaching it
$user->ownedUserTags()->create(['name' => 'Read Later']);
```

The relationship is called `ownedUserTags()` so a User model can also use `HasUserTags`, for example to let users tag each other, without the two traits colliding.

Without the trait, `UserTag::forUser()` gives the same query:

```php
use MujahidAbbas\LaravelUserTags\Models\UserTag;

$tags = UserTag::forUser($user)->orderBy('name')->get();
```

Search a user's tags by name with `containing()`, for example for autocomplete. Like tag matching, it ignores case and extra whitespace:

```php
$suggestions = $user->ownedUserTags()
    ->containing($term)
    ->orderBy('name')
    ->limit(10)
    ->get();

// Same, without the trait
$suggestions = UserTag::forUser($user)->containing($term)->get();
```

`containing()` does not limit the query to a user on its own: `UserTag::containing('imp')` searches every user's tags, so always chain it after `ownedUserTags()` or `forUser()`. `%` and `_` in the term act as SQL wildcards, and a blank term matches all of the user's tags.

### Using a Custom User

All tag methods accept an optional user parameter. This is useful when the authenticated user is not available:

```php
// In a queue job
public function handle()
{
    $user = User::find($this->userId);
    $article->attachUserTag('processed', $user);
}

// In a console command
$user = User::find($userId);
$article->syncUserTags(['archived'], $user);
```

If no user is authenticated and none is passed, a `NoAuthenticatedUserException` is thrown:

```php
use MujahidAbbas\LaravelUserTags\Exceptions\NoAuthenticatedUserException;

try {
    $article->attachUserTag('important');
} catch (NoAuthenticatedUserException $e) {
    // Handle missing user
}
```

## Important: MorphMap Configuration

This package uses polymorphic relationships to attach tags to any model. By default, Laravel stores the full class name (e.g., `App\Models\Article`) in the database.

**Why MorphMap matters:**

- If you rename or move a model class, existing tag associations break
- Full class names are verbose and expose your application structure
- Consistent short names make database queries cleaner

**Recommended: Configure MorphMap in your AppServiceProvider:**

```php
use Illuminate\Database\Eloquent\Relations\Relation;

public function boot(): void
{
    Relation::enforceMorphMap([
        'article' => \App\Models\Article::class,
        'post' => \App\Models\Post::class,
        'comment' => \App\Models\Comment::class,
        // Add all models that use HasUserTags
    ]);
}
```

This ensures stable, clean identifiers in your `user_taggables` table instead of full class paths.

## Upgrading from 1.3.0

Only MySQL and MariaDB installs need this. The `slug` column used the connection's collation, which treats `é` and `e` or `ß` and `ss` as equal, and with Laravel's default `utf8mb4_unicode_ci`, emoji such as `🔥` and `🍕` too. So tagging `resume` attached an existing `résumé` tag, `🍕` attached `🔥`, and `containing()` ignored accents. New installs get a binary collation on `slug`; for an existing install, add this migration:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }

        Schema::table(config('user-tags.tables.tags', 'user_tags'), function (Blueprint $table) {
            $table->string('slug')->collation('utf8mb4_bin')->change();
        });
    }
};
```

It keeps existing rows and indexes. Names that were already merged stay merged: the database never stored the second name, so those need re-tagging by hand.

## Upgrading from 1.1.0

The `slug` column now stores the lowercased name with extra whitespace removed, instead of `Str::slug()` output. Re-slug existing rows once after upgrading, for example from a migration, or names you tagged before will create duplicate tags:

```php
use MujahidAbbas\LaravelUserTags\Models\UserTag;

UserTag::query()->lazyById()->each(
    fn (UserTag $tag) => $tag->update(['slug' => UserTag::slugFromName($tag->name)])
);
```

This overwrites any slug you set by hand.

## Alternatives

If you need **global tags** (shared across all users), use [spatie/laravel-tags](https://github.com/spatie/laravel-tags) instead. It's excellent for content categorization, blog post tags, and similar use cases where tags are public.

This package is designed to **complement** spatie/laravel-tags, not replace it. You can use both in the same application:
- `spatie/laravel-tags` for public/global tags (e.g., blog categories)
- `mujahidabbas/laravel-user-tags` for private/user-scoped tags (e.g., personal bookmarks)

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Code Style

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Roadmap

Future releases may include:
- Events (`UserTagAttached`, `UserTagDetached`, `UserTagCreated`)
- Tag metadata (colors, icons via JSON column)
- Tag usage statistics

See the [GitHub issues](https://github.com/mujahidabbas/laravel-user-tags/issues) for feature requests and discussions.

## Credits

- [Mujahid Abbas](https://github.com/MujahidAbbas)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
