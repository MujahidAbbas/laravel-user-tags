<?php

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use MujahidAbbas\LaravelUserTags\Models\UserTag;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\Post;
use MujahidAbbas\LaravelUserTags\Tests\Fixtures\User;

// The other request writes on a second connection, which can't see rows inside
// RefreshDatabase's wrapping transaction, so these tests commit it. RefreshDatabase then
// migrates afresh before the next test. A second connection to SQLite's in-memory
// database opens a different database.
beforeEach(function () {
    if (DB::getDriverName() === 'sqlite') {
        $this->markTestSkipped('Needs a second connection to the same database.');
    }

    DB::commit();

    config(['database.connections.concurrent' => config('database.connections.'.DB::getDefaultConnection())]);

    $this->user = User::factory()->create();
    $this->post = Post::create(['title' => 'Test Post', 'body' => 'Content']);
});

test('a tag another request creates first is used', function (string $method, bool $attached) {
    // The other request commits the tag, and its attachment to the post, after this one
    // looked for the tag and before it inserts it
    $created = false;
    UserTag::creating(function (UserTag $tag) use (&$created, $attached) {
        if ($created) {
            return;
        }

        $created = true;
        $connection = DB::connection('concurrent');

        $tagId = $connection->table($tag->getTable())->insertGetId([
            'user_id' => $tag->user_id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($attached) {
            $connection->table($tag->getTaggablesTable())->insert([
                'tag_id' => $tagId,
                'taggable_id' => $this->post->getKey(),
                'taggable_type' => $this->post->getMorphClass(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    });

    $this->post->$method(['new name'], $this->user);

    expect(UserTag::pluck('name')->all())->toBe(['new name']);
    expect($this->post->userTags()->pluck('name')->all())->toBe(['new name']);
})->with(['attachUserTag', 'syncUserTags'])->with(['tag only' => false, 'tag and attachment' => true]);

test('an attachment another request creates first is used', function (string $method) {
    $tag = UserTag::create(['user_id' => $this->user->id, 'name' => 'existing']);

    // The other request attaches the tag after this one read the post's attachments: attach
    // reads the pivot table, sync joins it
    $attached = false;
    DB::listen(function (QueryExecuted $query) use (&$attached, $tag) {
        if ($attached || $query->connectionName === 'concurrent' || ! str_contains($query->sql, $tag->getTaggablesTable())) {
            return;
        }

        $attached = true;

        DB::connection('concurrent')->table($tag->getTaggablesTable())->insert([
            'tag_id' => $tag->id,
            'taggable_id' => $this->post->getKey(),
            'taggable_type' => $this->post->getMorphClass(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $this->post->$method(['existing'], $this->user);

    expect($attached)->toBeTrue();
    expect($this->post->userTags()->pluck('name')->all())->toBe(['existing']);
})->with(['attachUserTag', 'syncUserTags']);
