<?php

namespace MujahidAbbas\LaravelUserTags\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MujahidAbbas\LaravelUserTags\Models\UserTag;

trait OwnsUserTags
{
    /**
     * Get the tags owned by this user.
     *
     * Named ownedUserTags() rather than userTags() so a User model can
     * also use HasUserTags, where userTags() means the tags on the model.
     */
    public function ownedUserTags(): HasMany
    {
        return $this->hasMany(config('user-tags.tag_model', UserTag::class), 'user_id');
    }
}
