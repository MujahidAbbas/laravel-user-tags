<?php

namespace MujahidAbbas\LaravelUserTags\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
        static::creating(function (UserTag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
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
}
