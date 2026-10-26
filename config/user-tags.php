<?php

use MujahidAbbas\LaravelUserTags\Models\UserTag;

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Specify the fully qualified class name of your User model. This is used
    | for the belongsTo relationship on UserTag.
    |
    */
    'user_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Tag Model
    |--------------------------------------------------------------------------
    |
    | Specify the fully qualified class name of the Tag model. You can extend
    | the default UserTag model to add custom functionality.
    |
    */
    'tag_model' => UserTag::class,

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | You can customize the table names used by the package. This is useful
    | if you have existing tables with these names or prefer different naming.
    |
    */
    'tables' => [
        'tags' => 'user_tags',
        'taggables' => 'user_taggables',
    ],
];
