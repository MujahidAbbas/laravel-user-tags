<?php

namespace MujahidAbbas\LaravelUserTags\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserTag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('user-tags.tables.tags', 'user_tags');
    }

    /**
     * Get the pivot table name for taggables.
     */
    public function getTaggablesTable(): string
    {
        return config('user-tags.tables.taggables', 'user_taggables');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (UserTag $tag) {
            // Regenerate on rename too, unless a slug was set explicitly in the same save
            if (empty($tag->slug) || ($tag->isDirty('name') && ! $tag->isDirty('slug'))) {
                $tag->slug = static::slugFromName($tag->name);
            }
        });
    }

    /**
     * Get the slug used to match a tag name within a user's tags.
     *
     * Case and surrounding/repeated whitespace are ignored. Punctuation and
     * non-Latin characters are kept, so "C++" and "C#" stay separate tags.
     */
    public static function slugFromName(string $name): string
    {
        return Str::lower(Str::squish($name));
    }

    /**
     * Get the user that owns the tag.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('user-tags.user_model', 'App\\Models\\User'),
            'user_id'
        );
    }

    /**
     * Get all models of a given type that have this tag.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function taggedModels(string $modelClass): MorphToMany
    {
        return $this->morphedByMany(
            $modelClass,
            'taggable',
            $this->getTaggablesTable(),
            'tag_id',
            'taggable_id'
        );
    }

    /**
     * Scope a query to the tags owned by the given user.
     */
    public function scopeForUser(Builder $query, Authenticatable $user): Builder
    {
        return $query->where($this->qualifyColumn('user_id'), $user->getAuthIdentifier());
    }

    /**
     * Scope a query to tags whose name contains the given term.
     *
     * Matches on the slug, so case and extra whitespace are ignored on every
     * database. This does not limit the query to a user: chain it after
     * forUser() or ownedUserTags(). % and _ in the term act as wildcards.
     */
    public function scopeContaining(Builder $query, string $term): Builder
    {
        return $query->whereLike($this->qualifyColumn('slug'), '%'.static::slugFromName($term).'%');
    }
}
