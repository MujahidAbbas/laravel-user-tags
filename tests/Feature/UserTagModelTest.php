<?php

use Illuminate\Database\QueryException;
use MujahidAbbas\LaravelUserTags\Models\UserTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('auto-generates slug from name on create', function () {
    $tag = UserTag::create([
        'user_id' => $this->user->id,
        'name' => 'My Important Tag',
    ]);

    expect($tag->slug)->toBe('my-important-tag');
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
