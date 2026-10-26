<?php

namespace MujahidAbbas\LaravelUserTags\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use MujahidAbbas\LaravelUserTags\Traits\HasUserTags;
use MujahidAbbas\LaravelUserTags\Traits\OwnsUserTags;

class TaggableUser extends Authenticatable
{
    // Both traits in one class: a shared method name would be a fatal error here
    use HasUserTags;
    use OwnsUserTags;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';
}
