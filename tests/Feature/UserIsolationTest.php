<?php

use MujahidAbbas\LaravelUserTags\Exceptions\TagNotOwnedException;
use MujahidAbbas\LaravelUserTags\Models\UserTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();
    $this->post = Post::create(['title' => 'Shared Post', 'body' => 'Content']);
});

test('user cannot see other user tags on same model', function () {
    // User A attaches a tag
    $this->actingAs($this->userA);
    $this->post->attachUserTag('private-a');

    // User B attaches a different tag to the same post
    $this->actingAs($this->userB);
    $this->post->attachUserTag('private-b');

    $this->post->refresh();

    // Query tags filtered by user A
    $userATags = $this->post->userTags()->where('user_id', $this->userA->id)->get();
    expect($userATags)->toHaveCount(1);
    expect($userATags->first()->name)->toBe('private-a');

    // Query tags filtered by user B
    $userBTags = $this->post->userTags()->where('user_id', $this->userB->id)->get();
    expect($userBTags)->toHaveCount(1);
    expect($userBTags->first()->name)->toBe('private-b');

    // Total tags on post (both users' tags exist but are separate)
    expect($this->post->userTags)->toHaveCount(2);
});

test('user tags are scoped by user_id in database', function () {
    // Create tag with same name for user A
    UserTag::create([
        'user_id' => $this->userA->id,
        'name' => 'shared-name',
    ]);

    // Create tag with same name for user B
    UserTag::create([
        'user_id' => $this->userB->id,
        'name' => 'shared-name',
    ]);

    // Each user should have exactly one tag
    expect(UserTag::where('user_id', $this->userA->id)->count())->toBe(1);
    expect(UserTag::where('user_id', $this->userB->id)->count())->toBe(1);

    // Total tags with this name should be 2 (one per user)
    expect(UserTag::where('name', 'shared-name')->count())->toBe(2);
});

test('sync only affects current user tags', function () {
    // User A attaches a tag
    $this->actingAs($this->userA);
    $this->post->attachUserTag('user-a-tag');

    // User B attaches a tag
    $this->actingAs($this->userB);
    $this->post->attachUserTag('user-b-tag');

    // User B syncs (should only affect user B's tags)
    $this->post->syncUserTags(['new-b-tag']);

    $this->post->refresh();

    // User A's tag should still be attached
    $userATags = $this->post->userTags()->where('user_id', $this->userA->id)->get();
    expect($userATags)->toHaveCount(1);
    expect($userATags->first()->name)->toBe('user-a-tag');

    // User B's old tag removed, new tag attached
    $userBTags = $this->post->userTags()->where('user_id', $this->userB->id)->get();
    expect($userBTags)->toHaveCount(1);
    expect($userBTags->first()->name)->toBe('new-b-tag');
});

test('detach only removes current user attachment', function () {
    // User A attaches a tag named 'shared-name'
    $this->actingAs($this->userA);
    $this->post->attachUserTag('shared-name');

    // User B attaches a tag with the same name (but it's a different tag)
    $this->actingAs($this->userB);
    $this->post->attachUserTag('shared-name');

    // User B detaches their 'shared-name' tag
    $this->post->detachUserTag('shared-name');

    $this->post->refresh();

    // User A's 'shared-name' tag should still be attached
    $userATags = $this->post->userTags()->where('user_id', $this->userA->id)->get();
    expect($userATags)->toHaveCount(1);
    expect($userATags->first()->name)->toBe('shared-name');

    // User B's tag should be detached
    $userBTags = $this->post->userTags()->where('user_id', $this->userB->id)->get();
    expect($userBTags)->toHaveCount(0);
});

test('user cannot delete other user tags', function () {
    // Create a tag directly for user A
    $userATag = UserTag::create([
        'user_id' => $this->userA->id,
        'name' => 'protected',
    ]);

    // Acting as user B, try to find user A's tag
    $this->actingAs($this->userB);

    // User B cannot find user A's tag when querying by their own user_id
    $foundTag = UserTag::where('user_id', $this->userB->id)
        ->where('name', 'protected')
        ->first();

    expect($foundTag)->toBeNull();

    // User A's tag still exists in database
    expect(UserTag::where('id', $userATag->id)->exists())->toBeTrue();
});

test('explicit user parameter isolates correctly', function () {
    // Without actingAs, use explicit user parameter
    $this->post->attachUserTag('tag-a', $this->userA);
    $this->post->attachUserTag('tag-b', $this->userB);

    $this->post->refresh();

    // Query tags by user_id confirms isolation
    $userATags = $this->post->userTags()->where('user_id', $this->userA->id)->get();
    expect($userATags)->toHaveCount(1);
    expect($userATags->first()->name)->toBe('tag-a');

    $userBTags = $this->post->userTags()->where('user_id', $this->userB->id)->get();
    expect($userBTags)->toHaveCount(1);
    expect($userBTags->first()->name)->toBe('tag-b');
});

test('userTags relationship returns all users tags but operations are scoped', function () {
    // User A attaches a tag
    $this->actingAs($this->userA);
    $this->post->attachUserTag('a-tag');

    // User B attaches a tag
    $this->actingAs($this->userB);
    $this->post->attachUserTag('b-tag');

    $this->post->refresh();

    // The relationship returns ALL tags on the model (from all users)
    $allTags = $this->post->userTags;
    expect($allTags)->toHaveCount(2);

    // But operations are scoped: syncing as user B only affects user B's tags
    $this->post->syncUserTags(['b-new']);

    $this->post->refresh();

    // User A's tag still exists
    $userATags = $this->post->userTags()->where('user_id', $this->userA->id)->get();
    expect($userATags)->toHaveCount(1);
    expect($userATags->first()->name)->toBe('a-tag');

    // User B's tag was replaced
    $userBTags = $this->post->userTags()->where('user_id', $this->userB->id)->get();
    expect($userBTags)->toHaveCount(1);
    expect($userBTags->first()->name)->toBe('b-new');
});

test('attaching another user tag instance throws', function () {
    $tagOfA = UserTag::create(['user_id' => $this->userA->id, 'name' => 'secret']);

    $this->post->attachUserTag($tagOfA, $this->userB);
})->throws(TagNotOwnedException::class);

test('a name before another user tag instance throws and creates no tag', function (string $method) {
    $this->post->attachUserTag('b-tag', $this->userB);
    $tagOfA = UserTag::create(['user_id' => $this->userA->id, 'name' => 'secret']);

    // The names are created in order, so attach used to create this one before reaching the instance
    expect(fn () => $this->post->$method(['new name', $tagOfA], $this->userB))
        ->toThrow(TagNotOwnedException::class);

    expect(UserTag::orderBy('id')->pluck('name')->all())->toBe(['b-tag', 'secret']);
    expect($this->post->userTags()->pluck('name')->all())->toBe(['b-tag']);
})->with(['attachUserTag', 'syncUserTags', 'detachUserTag']);

test('syncing another user tag instance throws and leaves tags untouched', function () {
    $this->post->attachUserTag('b-tag', $this->userB);
    $tagOfA = UserTag::create(['user_id' => $this->userA->id, 'name' => 'secret']);

    expect(fn () => $this->post->syncUserTags([$tagOfA], $this->userB))
        ->toThrow(TagNotOwnedException::class);

    expect($this->post->userTags()->pluck('name')->all())->toBe(['b-tag']);
});

test('detaching another user tag instance throws and keeps the attachment', function () {
    $this->post->attachUserTag('mine', $this->userA);
    $tagOfA = UserTag::where('user_id', $this->userA->id)->first();

    expect(fn () => $this->post->detachUserTag($tagOfA, $this->userB))
        ->toThrow(TagNotOwnedException::class);

    expect($this->post->userTags()->count())->toBe(1);
});
