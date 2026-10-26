<?php

namespace MujahidAbbas\LaravelUserTags\Tests\Fixtures;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeletingPost extends Post
{
    use SoftDeletes;
}
