<?php

namespace MujahidAbbas\LaravelUserTags\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use MujahidAbbas\LaravelUserTags\Exceptions\NoAuthenticatedUserException;
use MujahidAbbas\LaravelUserTags\Exceptions\TagNotOwnedException;
use MujahidAbbas\LaravelUserTags\Models\UserTag;
use Stringable;

trait HasUserTags
{
    /**
     * Remove tag attachments when the model is deleted.
     *
     * The polymorphic pivot has no foreign key to the model, so nothing else cleans
     * it up. Soft deletes keep the attachments so a restore brings the tags back.
     */
    public static function bootHasUserTags(): void
    {
        static::deleted(function (self $model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->userTags()->detach();
        });
    }

    /**
     * Get all user tags attached to this model.
     *
     * This returns tags from all users. For one user's tags, use
     * userTagsFor(). For user-scoped tag operations, use attachUserTag(),
     * detachUserTag(), or syncUserTags() methods which filter by the
     * authenticated user.
     */
    public function userTags(): MorphToMany
    {
        return $this->morphToMany(
            $this->getUserTagClassName(),
            'taggable',
            $this->getUserTaggableTableName(),
            'taggable_id',
            'tag_id'
        )->withTimestamps();
    }

    /**
     * Get the tags the given user has attached to this model.
     *
     * The user defaults to the authenticated one, which is what lets
     * with('userTagsFor') eager load it: eager loading calls the method
     * without arguments. To eager load another user's tags, use
     * with(['userTags' => fn ($query) => $query->forUser($user)]).
     *
     * @param  Authenticatable|null  $user  Optional user override (defaults to Auth::user())
     *
     * @throws NoAuthenticatedUserException
     */
    public function userTagsFor(?Authenticatable $user = null): MorphToMany
    {
        return $this->userTags()->where(
            $this->getUserTagTableName().'.user_id',
            $this->resolveUser($user)->getAuthIdentifier()
        );
    }

    /**
     * Attach one or more tags to this model for the authenticated user.
     *
     * Creates tags if they don't exist. Uses syncWithoutDetaching to prevent duplicates.
     *
     * @param  string|iterable<string|UserTag>|UserTag  $tags  Tag name(s) or UserTag instance(s), in an array or Collection
     * @param  Authenticatable|null  $user  Optional user override (defaults to Auth::user())
     *
     * @throws NoAuthenticatedUserException
     * @throws TagNotOwnedException
     * @throws InvalidArgumentException
     */
    public function attachUserTag(string|iterable|Model $tags, ?Authenticatable $user = null): static
    {
        $user = $this->resolveUser($user);
        $tags = $this->findOrCreateUserTags($this->normalizeUserTags($tags), $user);

        $this->userTags()->syncWithoutDetaching($tags->pluck('id'));

        return $this;
    }

    /**
     * Detach one or more tags from this model for the authenticated user.
     *
     * Does not delete the tag itself, only removes the association.
     * Silently ignores tags that don't exist.
     *
     * @param  string|iterable<string|UserTag>|UserTag  $tags  Tag name(s) or UserTag instance(s), in an array or Collection
     * @param  Authenticatable|null  $user  Optional user override (defaults to Auth::user())
     *
     * @throws NoAuthenticatedUserException
     * @throws TagNotOwnedException
     * @throws InvalidArgumentException
     */
    public function detachUserTag(string|iterable|Model $tags, ?Authenticatable $user = null): static
    {
        $user = $this->resolveUser($user);
        $tags = $this->resolveUserTags($this->normalizeUserTags($tags), $user);

        $this->userTags()->detach($tags->pluck('id'));

        return $this;
    }

    /**
     * Sync tags for the authenticated user on this model.
     *
     * Replaces all of this user's tags on this model with exactly those specified.
     * Tags from other users are not affected.
     *
     * @param  string|iterable<string|UserTag>|UserTag  $tags  Tag name(s) or UserTag instance(s), in an array or Collection
     * @param  Authenticatable|null  $user  Optional user override (defaults to Auth::user())
     *
     * @throws NoAuthenticatedUserException
     * @throws TagNotOwnedException
     * @throws InvalidArgumentException
     */
    public function syncUserTags(string|iterable|Model $tags, ?Authenticatable $user = null): static
    {
        $user = $this->resolveUser($user);
        $tags = $this->normalizeUserTags($tags);

        // A failed attach must not leave the user's tags detached
        $this->getConnection()->transaction(function () use ($tags, $user) {
            $newTags = $this->findOrCreateUserTags($tags, $user);

            // Get existing tag IDs for this user on this model
            $existingIds = $this->userTags()
                ->where('user_id', $user->getAuthIdentifier())
                ->pluck($this->getUserTagTableName().'.id');

            // Detach existing tags for this user
            $this->userTags()->detach($existingIds);

            // Attach new tags
            $this->userTags()->attach($newTags->pluck('id'));
        });

        return $this;
    }

    /**
     * Resolve the user to use for tag operations.
     *
     * @param  Authenticatable|null  $user  Optional user override
     *
     * @throws NoAuthenticatedUserException
     */
    protected function resolveUser(?Authenticatable $user): Authenticatable
    {
        $user = $user ?? Auth::user();

        if ($user === null) {
            throw new NoAuthenticatedUserException;
        }

        return $user;
    }

    /**
     * Turn the tag input into a list of tag names and UserTag instances.
     *
     * Lists can be arrays or any iterable, such as a Collection, read once. Values
     * that cast to their JSON, such as another model or a nested Collection, are
     * rejected: outside strict_types PHP would turn them into a tag name.
     *
     * @param  string|iterable<mixed>|Model  $tags
     * @return list<string|UserTag>
     *
     * @throws InvalidArgumentException
     */
    protected function normalizeUserTags(string|iterable|Model $tags): array
    {
        $normalized = [];

        foreach (is_iterable($tags) ? $tags : [$tags] as $tag) {
            if ($tag instanceof UserTag) {
                $normalized[] = $tag;
            } elseif (is_string($tag) || is_int($tag) || is_float($tag) || ($tag instanceof Stringable && ! $tag instanceof Jsonable)) {
                $normalized[] = (string) $tag;
            } else {
                throw new InvalidArgumentException('Tags must be names or UserTag instances, '.get_debug_type($tag).' given.');
            }
        }

        return $normalized;
    }

    /**
     * Find or create tags for the given input.
     *
     * Accepts strings, arrays of strings, or UserTag instances.
     * Creates tags if they don't exist using firstOrCreate.
     * Blank names are skipped and names resolving to the same tag are returned once.
     *
     * @param  string|array<string|UserTag>|UserTag  $tags
     * @return Collection<int, UserTag>
     *
     * @throws TagNotOwnedException
     */
    protected function findOrCreateUserTags(string|array|UserTag $tags, Authenticatable $user): Collection
    {
        $tagClass = $this->getUserTagClassName();
        $tags = Arr::wrap($tags);

        // Every instance's owner is checked before any name is created, so another user's
        // tag can't leave the names before it in the user's tags. Instance checks here and
        // below use the base class: with a subclass as tag_model, UserTag::find() still
        // returns base instances, which would otherwise be cast to a JSON string and
        // treated as a tag name, skipping the ownership check
        collect($tags)
            ->filter(fn ($tag) => $tag instanceof UserTag)
            ->each(fn (UserTag $tag) => $this->ensureUserOwnsTag($tag, $user));

        return collect($tags)
            ->reject(fn ($tag) => ! $tag instanceof UserTag && $tagClass::slugFromName($tag) === '')
            ->map(function ($tag) use ($tagClass, $user) {
                if ($tag instanceof UserTag) {
                    return $tag;
                }

                return $tagClass::firstOrCreate(
                    [
                        'user_id' => $user->getAuthIdentifier(),
                        'slug' => $tagClass::slugFromName($tag),
                    ],
                    [
                        'name' => $tag,
                    ]
                );
            })
            ->unique(fn (UserTag $tag) => $tag->getKey())
            ->values();
    }

    /**
     * Resolve existing tags for the given input.
     *
     * Similar to findOrCreateUserTags but does not create tags.
     * Filters out tags that don't exist (silently ignores them).
     *
     * @param  string|array<string|UserTag>|UserTag  $tags
     * @return Collection<int, UserTag>
     *
     * @throws TagNotOwnedException
     */
    protected function resolveUserTags(string|array|UserTag $tags, Authenticatable $user): Collection
    {
        $tagClass = $this->getUserTagClassName();
        $tags = Arr::wrap($tags);

        return collect($tags)->map(function ($tag) use ($tagClass, $user) {
            if ($tag instanceof UserTag) {
                $this->ensureUserOwnsTag($tag, $user);

                return $tag;
            }

            return $tagClass::where('user_id', $user->getAuthIdentifier())
                ->where('slug', $tagClass::slugFromName($tag))
                ->first();
        })->filter();
    }

    /**
     * Ensure a tag instance passed in by the caller belongs to the given user.
     *
     * @throws TagNotOwnedException
     */
    protected function ensureUserOwnsTag(UserTag $tag, Authenticatable $user): void
    {
        if ((string) $tag->user_id !== (string) $user->getAuthIdentifier()) {
            throw new TagNotOwnedException;
        }
    }

    /**
     * Convert tag names and instances to their distinct, non-blank slugs.
     *
     * @param  string|array<string|UserTag>|UserTag  $tags
     * @return array<string>
     */
    protected function resolveTagSlugs(string|array|UserTag $tags): array
    {
        $tagClass = $this->getUserTagClassName();

        return collect(Arr::wrap($tags))
            ->map(fn ($tag) => $tag instanceof UserTag ? $tag->slug : $tagClass::slugFromName($tag))
            ->reject(fn ($slug) => $slug === '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve tag names and instances to IDs for the given user.
     *
     * Used by query scopes to convert user input to efficient ID-based queries.
     * Returns empty array if tags input is empty or no matching tags found.
     *
     * @param  string|array<string|UserTag>|UserTag  $tags
     * @return array<int>
     *
     * @throws TagNotOwnedException
     */
    protected function resolveTagIds(string|array|UserTag $tags, Authenticatable $user): array
    {
        $tagClass = $this->getUserTagClassName();

        collect(Arr::wrap($tags))
            ->filter(fn ($tag) => $tag instanceof UserTag)
            ->each(fn (UserTag $tag) => $this->ensureUserOwnsTag($tag, $user));

        // Instances resolve through their slug like names do, so a tag passed both
        // ways counts once, and every ID comes from a query limited to the user
        $slugs = $this->resolveTagSlugs($tags);

        if (empty($slugs)) {
            return [];
        }

        return $tagClass::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();
    }

    /**
     * Scope to get models having any of the given tags.
     *
     * @param  string|iterable<string|UserTag>|UserTag  $tags  Tag name(s) or UserTag instance(s), in an array or Collection
     * @param  Authenticatable|null  $user  Optional user override
     *
     * @throws NoAuthenticatedUserException
     * @throws TagNotOwnedException
     * @throws InvalidArgumentException
     */
    public function scopeWithAnyUserTags(
        Builder $query,
        string|iterable|Model $tags,
        ?Authenticatable $user = null
    ): Builder {
        $user = $this->resolveUser($user);
        $tagIds = $this->resolveTagIds($this->normalizeUserTags($tags), $user);

        // No matching tags = no results
        if (empty($tagIds)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('userTags', function (Builder $q) use ($tagIds) {
            $q->whereIn($this->getUserTagTableName().'.id', $tagIds);
        });
    }

    /**
     * Scope to get models having all of the given tags.
     *
     * @param  string|iterable<string|UserTag>|UserTag  $tags  Tag name(s) or UserTag instance(s), in an array or Collection
     * @param  Authenticatable|null  $user  Optional user override
     *
     * @throws NoAuthenticatedUserException
     * @throws TagNotOwnedException
     * @throws InvalidArgumentException
     */
    public function scopeWithAllUserTags(
        Builder $query,
        string|iterable|Model $tags,
        ?Authenticatable $user = null
    ): Builder {
        $user = $this->resolveUser($user);
        // Normalized once: the input is read twice below, and a generator can't be
        $tags = $this->normalizeUserTags($tags);
        $tagIds = $this->resolveTagIds($tags, $user);

        // If any tag doesn't exist, no model can have "all" of them
        if (count($tagIds) !== count($this->resolveTagSlugs($tags))) {
            return $query->whereRaw('0 = 1');
        }

        // Each tag adds another whereHas constraint (AND logic)
        foreach ($tagIds as $tagId) {
            $query->whereHas('userTags', function (Builder $q) use ($tagId) {
                $q->where($this->getUserTagTableName().'.id', $tagId);
            });
        }

        return $query;
    }

    /**
     * Scope to get models NOT having any of the given tags.
     *
     * @param  string|iterable<string|UserTag>|UserTag  $tags  Tag name(s) or UserTag instance(s), in an array or Collection
     * @param  Authenticatable|null  $user  Optional user override
     *
     * @throws NoAuthenticatedUserException
     * @throws TagNotOwnedException
     * @throws InvalidArgumentException
     */
    public function scopeWithoutUserTags(
        Builder $query,
        string|iterable|Model $tags,
        ?Authenticatable $user = null
    ): Builder {
        $user = $this->resolveUser($user);
        $tagIds = $this->resolveTagIds($this->normalizeUserTags($tags), $user);

        // No matching tags = return all (nothing to exclude)
        if (empty($tagIds)) {
            return $query;
        }

        return $query->whereDoesntHave('userTags', function (Builder $q) use ($tagIds) {
            $q->whereIn($this->getUserTagTableName().'.id', $tagIds);
        });
    }

    /**
     * Get the user tag model class name from config.
     */
    protected function getUserTagClassName(): string
    {
        return config('user-tags.tag_model', UserTag::class);
    }

    /**
     * Get the taggables pivot table name from config.
     */
    protected function getUserTaggableTableName(): string
    {
        return config('user-tags.tables.taggables', 'user_taggables');
    }

    /**
     * Get the tags table name from config.
     */
    protected function getUserTagTableName(): string
    {
        return config('user-tags.tables.tags', 'user_tags');
    }
}
