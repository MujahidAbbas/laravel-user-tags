<?php

use Illuminate\Database\QueryException;
use MujahidAbbas\LaravelUserTags\Models\UserTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('auto-generates slug from name on create', function () {
    $tag = UserTag::create([
        'user_id' => $this->user->id,
        'name' => 'My Important Tag',
    ]);

    expect($tag->slug)->toBe('my important tag');
});

test('does not override provided slug', function () {
    $tag = UserTag::create([
        'user_id' => $this->user->id,
        'name' => 'Test',
        'slug' => 'custom-slug',
    ]);

    expect($tag->slug)->toBe('custom-slug');
});

test('belongs to user', function () {
    $tag = UserTag::create([
        'user_id' => $this->user->id,
        'name' => 'Test Tag',
    ]);

    expect($tag->user->id)->toBe($this->user->id);
    expect($tag->user)->toBeInstanceOf(User::class);
});

test('enforces unique slug per user constraint', function () {
    UserTag::create([
        'user_id' => $this->user->id,
        'name' => 'First',
        'slug' => 'duplicate',
    ]);

    UserTag::create([
        'user_id' => $this->user->id,
        'name' => 'Second',
        'slug' => 'duplicate',
    ]);
})->throws(QueryException::class);

test('allows same slug for different users', function () {
    $userA = $this->user;
    $userB = User::factory()->create();

    UserTag::create([
        'user_id' => $userA->id,
        'name' => 'Shared Tag',
        'slug' => 'shared',
    ]);

    UserTag::create([
        'user_id' => $userB->id,
        'name' => 'Shared Tag',
        'slug' => 'shared',
    ]);

    $tags = UserTag::where('slug', 'shared')->get();

    expect($tags)->toHaveCount(2);
    expect($tags->pluck('slug')->unique()->first())->toBe('shared');
});

test('uses configured table name', function () {
    expect((new UserTag)->getTable())->toBe('user_tags');
});

test('names that differ only by punctuation stay separate tags', function () {
    $post = Post::create(['title' => 'P']);

    $post->attachUserTag(['C++', 'C#'], $this->user);

    expect(UserTag::pluck('name')->sort()->values()->all())->toBe(['C#', 'C++']);
});

test('non-latin and emoji names get their own tags', function () {
    $post = Post::create(['title' => 'P']);

    $post->attachUserTag(['重要', '🔥', 'اہم'], $this->user);

    expect(UserTag::count())->toBe(3);
});

test('names that differ by accents, sharp s or emoji stay separate tags', function (array $names) {
    $post = Post::create(['title' => 'P']);

    $post->attachUserTag($names, $this->user);

    expect(UserTag::orderBy('id')->pluck('name')->all())->toBe($names);
})->with([
    'accents' => [['résumé', 'resume']],
    'sharp s' => [['straße', 'strasse']],
    'emoji' => [['🔥', '🍕']],
]);

test('renaming a tag updates its slug', function () {
    $post = Post::create(['title' => 'P']);
    $post->attachUserTag('Old Name', $this->user);

    UserTag::first()->update(['name' => 'New Name']);

    expect(UserTag::first()->slug)->toBe('new name');
    expect(Post::withAnyUserTags(['New Name'], $this->user)->count())->toBe(1);
    expect(Post::withAnyUserTags(['Old Name'], $this->user)->count())->toBe(0);
});

test('renaming with an explicit slug keeps that slug', function () {
    $tag = UserTag::create(['user_id' => $this->user->id, 'name' => 'Before']);

    $tag->update(['name' => 'After', 'slug' => 'custom']);

    expect($tag->fresh()->slug)->toBe('custom');
});
