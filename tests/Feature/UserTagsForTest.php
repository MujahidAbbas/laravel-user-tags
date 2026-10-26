<?php

use Illuminate\Support\Facades\DB;
use MujahidAbbas\LaravelUserTags\Exceptions\NoAuthenticatedUserException;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();
    $this->post1 = Post::create(['title' => 'Post 1', 'body' => 'Content']);
    $this->post2 = Post::create(['title' => 'Post 2', 'body' => 'Content']);

    $this->post1->attachUserTag(['a-first', 'shared'], $this->userA);
    $this->post1->attachUserTag(['b-only', 'shared'], $this->userB);
    $this->post2->attachUserTag('a-second', $this->userA);
});

test('userTagsFor returns only the given user tags', function () {
    $tags = $this->post1->userTagsFor($this->userB)->pluck('name')->sort()->values()->all();

    expect($tags)->toBe(['b-only', 'shared']);
});

test('userTagsFor defaults to the authenticated user', function () {
    $this->actingAs($this->userA);

    expect($this->post1->userTagsFor->pluck('name')->sort()->values()->all())->toBe(['a-first', 'shared']);
});

test('userTagsFor eager loads by name for the authenticated user', function () {
    $this->actingAs($this->userA);

    DB::enableQueryLog();
    $posts = Post::with('userTagsFor')->orderBy('id')->get();
    $names = $posts->map(fn (Post $post) => $post->userTagsFor->pluck('name')->sort()->values()->all())->all();

    expect($names)->toBe([['a-first', 'shared'], ['a-second']]);
    expect(DB::getQueryLog())->toHaveCount(2);
});

test('another user tags eager load through forUser on userTags', function () {
    $posts = Post::with(['userTags' => fn ($query) => $query->forUser($this->userB)])->orderBy('id')->get();
    $names = $posts->map(fn (Post $post) => $post->userTags->pluck('name')->sort()->values()->all())->all();

    expect($names)->toBe([['b-only', 'shared'], []]);
});

test('userTagsFor throws without a user', function () {
    $this->post1->userTagsFor();
})->throws(NoAuthenticatedUserException::class);

test('eager loading userTagsFor throws without a user', function () {
    Post::with('userTagsFor')->get();
})->throws(NoAuthenticatedUserException::class);
