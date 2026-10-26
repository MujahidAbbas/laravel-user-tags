<?php

use MujahidAbbas\LaravelUserTags\Models\UserTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\TaggableUser;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();
    $this->post = Post::create(['title' => 'Shared Post', 'body' => 'Content']);
});

test('ownedUserTags returns only the user own tags', function () {
    $this->post->attachUserTag(['work', 'home'], $this->userA);
    $this->post->attachUserTag('work', $this->userB);

    expect($this->userA->ownedUserTags->pluck('name')->sort()->values()->all())->toBe(['home', 'work']);
    expect($this->userB->ownedUserTags->pluck('name')->all())->toBe(['work']);
});

test('ownedUserTags includes tags not attached to any model', function () {
    $this->userA->ownedUserTags()->create(['name' => 'Unused']);

    $tag = UserTag::first();

    expect($tag->user_id)->toBe($this->userA->id);
    expect($tag->slug)->toBe('unused');
    expect($this->userA->ownedUserTags()->count())->toBe(1);
});

test('ownedUserTags chains with containing', function () {
    $this->post->attachUserTag(['Project Alpha', 'Personal'], $this->userA);
    $this->post->attachUserTag('Project Beta', $this->userB);

    expect($this->userA->ownedUserTags()->containing('project')->pluck('name')->all())->toBe(['Project Alpha']);
});

test('a user model can use both HasUserTags and OwnsUserTags', function () {
    $tagger = TaggableUser::find($this->userA->id);
    $teammate = TaggableUser::find($this->userB->id);

    $teammate->attachUserTag('reviewer', $tagger);

    expect($tagger->ownedUserTags->pluck('name')->all())->toBe(['reviewer']);
    expect($teammate->userTags->pluck('name')->all())->toBe(['reviewer']);
    expect($teammate->ownedUserTags)->toBeEmpty();
});
