<?php

use Illuminate\Support\Facades\DB;
use MujahidAbbas\LaravelUserTags\Exceptions\NoAuthenticatedUserException;
use MujahidAbbas\LaravelUserTags\Models\UserTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\SoftDeletingPost;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->post = Post::create(['title' => 'Test Post', 'body' => 'Content']);
});

// attachUserTag tests

test('can attach tag by string', function () {
    $this->actingAs($this->user);

    $this->post->attachUserTag('important');

    expect($this->post->userTags)->toHaveCount(1);
    expect($this->post->userTags->first()->name)->toBe('important');
});

test('can attach multiple tags by array', function () {
    $this->actingAs($this->user);

    $this->post->attachUserTag(['urgent', 'review']);

    expect($this->post->userTags)->toHaveCount(2);
});

test('can attach existing UserTag instance', function () {
    $tag = UserTag::create([
        'user_id' => $this->user->id,
        'name' => 'existing',
    ]);

    $this->actingAs($this->user);

    $this->post->attachUserTag($tag);

    expect($this->post->userTags)->toHaveCount(1);
});

test('creates tag if not exists when attaching', function () {
    $this->actingAs($this->user);

    $this->post->attachUserTag('new-tag');

    expect(UserTag::where('name', 'new-tag')->exists())->toBeTrue();
});

test('does not duplicate tag when attaching same tag twice', function () {
    $this->actingAs($this->user);

    $this->post->attachUserTag('duplicate');
    $this->post->attachUserTag('duplicate');

    $this->post->refresh();

    expect($this->post->userTags)->toHaveCount(1);
});

// detachUserTag tests

test('can detach tag by string', function () {
    $this->actingAs($this->user);

    $this->post->attachUserTag('removable');
    $this->post->detachUserTag('removable');

    $this->post->refresh();

    expect($this->post->userTags)->toHaveCount(0);
});

test('detach does not delete the tag itself', function () {
    $this->actingAs($this->user);

    $this->post->attachUserTag('permanent');
    $this->post->detachUserTag('permanent');

    expect(UserTag::where('name', 'permanent')->exists())->toBeTrue();
});

test('detach silently ignores non-existent tags', function () {
    $this->actingAs($this->user);

    // This should not throw an exception
    $this->post->detachUserTag('non-existent');

    expect(true)->toBeTrue();
});

// syncUserTags tests

test('sync replaces all tags', function () {
    $this->actingAs($this->user);

    $this->post->attachUserTag(['old1', 'old2']);
    $this->post->syncUserTags(['new1', 'new2']);

    $this->post->refresh();

    $tagNames = $this->post->userTags->pluck('name')->toArray();

    expect($this->post->userTags)->toHaveCount(2);
    expect($tagNames)->toContain('new1');
    expect($tagNames)->toContain('new2');
    expect($tagNames)->not->toContain('old1');
    expect($tagNames)->not->toContain('old2');
});

test('sync with empty array removes all tags', function () {
    $this->actingAs($this->user);

    $this->post->attachUserTag(['tag1', 'tag2']);
    $this->post->syncUserTags([]);

    $this->post->refresh();

    expect($this->post->userTags)->toHaveCount(0);
});

// Authentication tests

test('throws exception when no user authenticated', function () {
    $this->post->attachUserTag('test');
})->throws(NoAuthenticatedUserException::class);

test('can pass user explicitly without authentication', function () {
    $this->post->attachUserTag('explicit', $this->user);

    expect($this->post->userTags)->toHaveCount(1);
});

// Method chaining test

test('methods return model instance for chaining', function () {
    $this->actingAs($this->user);

    $attachResult = $this->post->attachUserTag('chain');
    expect($attachResult)->toBe($this->post);

    $detachResult = $this->post->detachUserTag('chain');
    expect($detachResult)->toBe($this->post);

    $syncResult = $this->post->syncUserTags(['new']);
    expect($syncResult)->toBe($this->post);
});

test('attach treats names that differ only by case or spacing as one tag', function () {
    $this->post->attachUserTag(['Important', ' important ', 'IMPORTANT'], $this->user);

    expect($this->post->userTags()->count())->toBe(1);
    expect(UserTag::count())->toBe(1);
});

test('attach skips blank tag names', function () {
    $this->post->attachUserTag(['', '   ', 'real'], $this->user);

    expect(UserTag::pluck('name')->all())->toBe(['real']);
});

test('sync with names that differ only by case attaches one tag', function () {
    $this->post->attachUserTag(['old1', 'old2'], $this->user);

    $this->post->syncUserTags(['Important', 'important'], $this->user);

    expect($this->post->userTags()->pluck('name')->all())->toBe(['Important']);
});

// Model deletion tests

test('deleting a model removes its tag attachments', function () {
    $this->post->attachUserTag(['x', 'y'], $this->user);

    $this->post->delete();

    expect(DB::table('user_taggables')->count())->toBe(0);
    expect(UserTag::count())->toBe(2);
});

test('soft deleting a model keeps its tag attachments', function () {
    $post = SoftDeletingPost::create(['title' => 'Soft']);
    $post->attachUserTag('kept', $this->user);

    $post->delete();

    expect(DB::table('user_taggables')->count())->toBe(1);
});

test('force deleting a soft deletable model removes its tag attachments', function () {
    $post = SoftDeletingPost::create(['title' => 'Soft']);
    $post->attachUserTag('gone', $this->user);

    $post->forceDelete();

    expect(DB::table('user_taggables')->count())->toBe(0);
});

test('sync keeps existing tags when the attach fails', function () {
    $this->post->attachUserTag('keep-me', $this->user);
    $deleted = UserTag::create(['user_id' => $this->user->id, 'name' => 'deleted']);
    $deleted->delete();

    // The stale instance's id no longer exists, so the attach hits the foreign key
    expect(fn () => $this->post->syncUserTags([$deleted], $this->user))->toThrow(Exception::class);

    expect($this->post->userTags()->pluck('name')->all())->toBe(['keep-me']);
});
