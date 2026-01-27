<?php

namespace MujahidAbbas\LaravelUserTags\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MujahidAbbas\LaravelUserTags\Traits\HasUserTags;

class Post extends Model
{
    use HasUserTags;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['title', 'body'];
}
