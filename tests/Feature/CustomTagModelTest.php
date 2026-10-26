<?php

use MujahidAbbas\LaravelUserTags\Exceptions\TagNotOwnedException;
use MujahidAbbas\LaravelUserTags\Models\UserTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\CustomTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    config(['user-tags.tag_model' => CustomTag::class]);

    $this->owner = User::factory()->create();
    $this->other = User::factory()->create();
    $this->post = Post::create(['title' => 'Post', 'body' => 'Content']);
    $this->post->attachUserTag('secret', $this->owner);

    // A base instance, as UserTag::findOrFail($request->input('tag_id')) returns
    $this->baseTag = UserTag::first();
});

test('a base UserTag instance attaches that tag', function () {
    $post = Post::create(['title' => 'Second', 'body' => 'Content']);

    $post->attachUserTag($this->baseTag, $this->owner);

    expect($post->userTags->pluck('id')->all())->toBe([$this->baseTag->id]);
    expect(UserTag::count())->toBe(1);
});

test('another user base UserTag instance throws and creates no tag', function (string $method) {
    expect(fn () => $this->post->$method($this->baseTag, $this->other))->toThrow(TagNotOwnedException::class);
    expect(UserTag::pluck('name')->all())->toBe(['secret']);
})->with(['attachUserTag', 'detachUserTag', 'syncUserTags']);

test('detach and sync accept a base UserTag instance', function () {
    $this->post->detachUserTag($this->baseTag, $this->owner);
    expect($this->post->userTags()->count())->toBe(0);

    $this->post->syncUserTags($this->baseTag, $this->owner);
    expect($this->post->userTags()->pluck('name')->all())->toBe(['secret']);
});

test('query scopes accept a base UserTag instance', function () {
    expect(Post::withAnyUserTags($this->baseTag, $this->owner)->count())->toBe(1);
    expect(Post::withAllUserTags([$this->baseTag, 'Secret'], $this->owner)->count())->toBe(1);
    expect(Post::withoutUserTags($this->baseTag, $this->owner)->count())->toBe(0);
});

test('query scopes throw for another user base UserTag instance', function (string $scope) {
    Post::$scope($this->baseTag, $this->other)->get();
})->with(['withAnyUserTags', 'withAllUserTags', 'withoutUserTags'])->throws(TagNotOwnedException::class);
