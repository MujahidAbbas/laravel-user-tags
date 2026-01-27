<?php

use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->post1 = Post::create(['title' => 'Post 1', 'body' => 'Content']);
    $this->post2 = Post::create(['title' => 'Post 2', 'body' => 'Content']);
    $this->post3 = Post::create(['title' => 'Post 3', 'body' => 'Content']);
});

// withAnyUserTags tests

test('withAnyUserTags returns posts having tag', function () {
    $this->actingAs($this->user);

    $this->post1->attachUserTag('important');
    $this->post2->attachUserTag('other');

    $results = Post::withAnyUserTags(['important'])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($this->post1->id);
});

test('withAnyUserTags returns posts having any of multiple tags', function () {
    $this->actingAs($this->user);

    $this->post1->attachUserTag('important');
    $this->post2->attachUserTag('urgent');
    $this->post3->attachUserTag('other');

    $results = Post::withAnyUserTags(['important', 'urgent'])->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->all())->toContain($this->post1->id);
    expect($results->pluck('id')->all())->toContain($this->post2->id);
});

test('withAnyUserTags excludes posts without matching tags', function () {
    $this->actingAs($this->user);

    $this->post1->attachUserTag('important');
    $this->post2->attachUserTag('urgent');
    $this->post3->attachUserTag('other');

    $results = Post::withAnyUserTags(['important', 'urgent'])->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->all())->not->toContain($this->post3->id);
});

// withAllUserTags tests

test('withAllUserTags returns posts having all tags', function () {
    $this->actingAs($this->user);

    $this->post1->attachUserTag(['priority', 'review']);
    $this->post2->attachUserTag('priority');
    $this->post3->attachUserTag('review');

    $results = Post::withAllUserTags(['priority', 'review'])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($this->post1->id);
});

test('withAllUserTags excludes posts missing any tag', function () {
    $this->actingAs($this->user);

    $this->post1->attachUserTag(['priority', 'review']);
    $this->post2->attachUserTag('priority'); // Missing 'review'

    $results = Post::withAllUserTags(['priority', 'review'])->get();

    expect($results)->toHaveCount(1);
    expect($results->pluck('id')->all())->not->toContain($this->post2->id);
});

// withoutUserTags tests

test('withoutUserTags excludes posts with tag', function () {
    $this->actingAs($this->user);

    $this->post1->attachUserTag('spam');
    $this->post2->attachUserTag('important');
    // post3 has no tags

    $results = Post::withoutUserTags(['spam'])->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->all())->toContain($this->post2->id);
    expect($results->pluck('id')->all())->toContain($this->post3->id);
    expect($results->pluck('id')->all())->not->toContain($this->post1->id);
});

// User isolation tests

test('scopes are user isolated', function () {
    $userA = $this->user;
    $userB = User::factory()->create();

    // User A tags post1 as 'important'
    $this->actingAs($userA);
    $this->post1->attachUserTag('important');

    // User B tags post2 as 'important'
    $this->actingAs($userB);
    $this->post2->attachUserTag('important');

    // User A's scope should only find post1
    $resultsA = Post::withAnyUserTags(['important'], $userA)->get();
    expect($resultsA)->toHaveCount(1);
    expect($resultsA->first()->id)->toBe($this->post1->id);

    // User B's scope should only find post2
    $resultsB = Post::withAnyUserTags(['important'], $userB)->get();
    expect($resultsB)->toHaveCount(1);
    expect($resultsB->first()->id)->toBe($this->post2->id);
});
