<?php

use MujahidAbbas\LaravelUserTags\Models\UserTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();
    $this->post = Post::create(['title' => 'Shared Post', 'body' => 'Content']);
});

test('forUser returns only that user tags', function () {
    $this->post->attachUserTag(['work', 'home'], $this->userA);
    $this->post->attachUserTag('work', $this->userB);

    $tags = UserTag::forUser($this->userA)->orderBy('name')->pluck('name')->all();

    expect($tags)->toBe(['home', 'work']);
});

test('forUser chains onto a model userTags relation', function () {
    $this->post->attachUserTag('a-only', $this->userA);
    $this->post->attachUserTag('b-only', $this->userB);

    expect($this->post->userTags()->forUser($this->userB)->pluck('name')->all())->toBe(['b-only']);
});

test('containing ignores case and extra whitespace', function () {
    $this->post->attachUserTag(['Important Stuff', 'Later'], $this->userA);

    $tags = UserTag::forUser($this->userA)->containing('  IMPORTANT   stuff ')->pluck('name')->all();

    expect($tags)->toBe(['Important Stuff']);
});

test('containing ignores case for non-latin letters', function () {
    $this->post->attachUserTag(['Ärger', 'Arbeit'], $this->userA);

    expect(UserTag::forUser($this->userA)->containing('ÄR')->pluck('name')->all())->toBe(['Ärger']);
});

test('containing is not limited to a user unless chained after forUser', function () {
    $this->post->attachUserTag('project-a', $this->userA);
    $this->post->attachUserTag('project-b', $this->userB);

    expect(UserTag::containing('project')->count())->toBe(2);
    expect(UserTag::forUser($this->userA)->containing('project')->pluck('name')->all())->toBe(['project-a']);
});

test('containing with a blank term returns all of the user tags', function () {
    $this->post->attachUserTag(['one', 'two'], $this->userA);

    expect(UserTag::forUser($this->userA)->containing(' ')->count())->toBe(2);
});
