<?php

use MujahidAbbas\LaravelUserTags\Exceptions\TagNotOwnedException;
use MujahidAbbas\LaravelUserTags\Models\UserTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->other = User::factory()->create();
    $this->post = Post::create(['title' => 'Post', 'body' => 'Content']);
});

// Collections

test('attach accepts a collection of names', function () {
    $this->post->attachUserTag(collect(['alpha', 'beta']), $this->user);

    expect($this->post->userTags()->pluck('name')->sort()->values()->all())->toBe(['alpha', 'beta']);
});

test('sync accepts the user own tags as an eloquent collection', function () {
    $this->post->attachUserTag(['keep', 'drop'], $this->user);
    $ids = UserTag::where('name', 'keep')->pluck('id');

    $this->post->syncUserTags(UserTag::findMany($ids), $this->user);

    expect($this->post->userTags()->pluck('name')->all())->toBe(['keep']);
});

test('detach accepts a collection', function () {
    $this->post->attachUserTag(['one', 'two', 'three'], $this->user);

    $this->post->detachUserTag(collect(['one', 'two']), $this->user);

    expect($this->post->userTags()->pluck('name')->all())->toBe(['three']);
});

test('another user tags in a collection throw and create no tag', function (string $method) {
    $this->post->attachUserTag('mine', $this->user);
    $this->other->ownedUserTags()->create(['name' => 'theirs']);

    // Outside strict_types this collection used to be cast to its JSON, which became a tag name
    expect(fn () => $this->post->$method(UserTag::findMany(UserTag::pluck('id')), $this->user))
        ->toThrow(TagNotOwnedException::class);

    expect(UserTag::orderBy('id')->pluck('name')->all())->toBe(['mine', 'theirs']);
    expect($this->post->userTags()->pluck('name')->all())->toBe(['mine']);
})->with(['attachUserTag', 'syncUserTags', 'detachUserTag']);

test('scopes accept a collection', function () {
    $second = Post::create(['title' => 'Second', 'body' => 'Content']);
    $this->post->attachUserTag(['red', 'blue'], $this->user);
    $second->attachUserTag('red', $this->user);

    expect(Post::withAnyUserTags(collect(['blue']), $this->user)->pluck('id')->all())->toBe([$this->post->id]);
    expect(Post::withAllUserTags(collect(['red', 'blue']), $this->user)->pluck('id')->all())->toBe([$this->post->id]);
    expect(Post::withoutUserTags(collect(['blue']), $this->user)->pluck('id')->all())->toBe([$second->id]);
});

test('withAllUserTags reads a generator once', function () {
    $this->post->attachUserTag(['red', 'blue'], $this->user);

    $tags = (function () {
        yield 'red';
        yield 'blue';
    })();

    expect(Post::withAllUserTags($tags, $this->user)->pluck('id')->all())->toBe([$this->post->id]);
});

test('scopes throw for another user tags in a collection', function (string $scope) {
    $this->other->ownedUserTags()->create(['name' => 'theirs']);

    Post::$scope($this->other->ownedUserTags, $this->user)->get();
})->with(['withAnyUserTags', 'withAllUserTags', 'withoutUserTags'])->throws(TagNotOwnedException::class);

// Values that are not tag names

test('values that are not names or tags throw and change nothing', function (Closure $tags) {
    $this->post->attachUserTag('mine', $this->user);

    expect(fn () => $this->post->syncUserTags($tags(), $this->user))->toThrow(InvalidArgumentException::class);

    expect(UserTag::pluck('name')->all())->toBe(['mine']);
    expect($this->post->userTags()->pluck('name')->all())->toBe(['mine']);
})->with([
    'a model' => fn () => Post::create(['title' => 'Other']),
    'a model in a list' => fn () => [Post::create(['title' => 'Other'])],
    'a nested collection' => fn () => [collect(['a'])],
    'a nested array' => fn () => [['a']],
    'null' => fn () => [null],
]);

test('attach and scopes throw for a model that is not a tag', function () {
    expect(fn () => $this->post->attachUserTag($this->other, $this->user))->toThrow(InvalidArgumentException::class);
    expect(fn () => Post::withAnyUserTags([$this->other], $this->user))->toThrow(InvalidArgumentException::class);
    expect(UserTag::count())->toBe(0);
});

test('numbers and stringable values are still tag names', function () {
    $this->post->attachUserTag([2024, str('Foo')], $this->user);

    expect(UserTag::orderBy('id')->pluck('name')->all())->toBe(['2024', 'Foo']);
});
