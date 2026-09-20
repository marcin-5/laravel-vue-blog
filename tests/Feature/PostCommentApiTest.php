<?php

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('allows guests to read public threads but hides registered threads', function () {
    $author = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'user_id' => $author->id,
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
    ]);
    Thread::factory()->create(['post_id' => $post->id, 'user_id' => $author->id, 'visibility' => Thread::VIS_PUBLIC]);
    $registeredThread = Thread::factory()->create([
        'post_id' => $post->id,
        'user_id' => $author->id,
        'visibility' => Thread::VIS_REGISTERED,
    ]);

    $response = $this->getJson('/posts/' . $post->id . '/threads');

    $response->assertOk()->assertJsonCount(1);
    expect($response->json('0.visibility'))->toBe(Thread::VIS_PUBLIC);

    $this->getJson('/threads/' . $registeredThread->id . '/comments')->assertForbidden();
});

it('allows authenticated users to read registered threads', function () {
    $author = User::factory()->create();
    $viewer = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'user_id' => $author->id,
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
    ]);
    Thread::factory()->count(2)->create([
        'post_id' => $post->id,
        'user_id' => $author->id,
        'visibility' => Thread::VIS_PUBLIC,
    ]);
    Thread::factory()->create([
        'post_id' => $post->id,
        'user_id' => $author->id,
        'visibility' => Thread::VIS_REGISTERED,
    ]);

    $response = actingAs($viewer)->getJson('/posts/' . $post->id . '/threads');

    $response->assertOk()->assertJsonCount(3);
});

it('requires authentication to create a public blog thread', function () {
    $author = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id, 'is_published' => true]);
    $post = Post::factory()->create(['blog_id' => $blog->id, 'user_id' => $author->id]);

    $response = $this->postJson('/posts/' . $post->id . '/threads', [
        'title' => 'Guest topic',
        'visibility' => Thread::VIS_PUBLIC,
        'content' => 'Guest content',
    ]);

    $response->assertUnauthorized();
});

it('creates a thread and its initial comment transactionally', function () {
    $author = User::factory()->create();
    $participant = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id, 'is_published' => true]);
    $post = Post::factory()->create(['blog_id' => $blog->id, 'user_id' => $author->id]);

    $response = actingAs($participant)->postJson('/posts/' . $post->id . '/threads', [
        'title' => 'A new topic',
        'visibility' => Thread::VIS_REGISTERED,
        'content' => 'The opening message',
    ]);

    $response->assertCreated()->assertJsonPath('title', 'A new topic');
    $threadId = $response->json('id');

    $this->assertDatabaseHas('threads', [
        'id' => $threadId,
        'post_id' => $post->id,
        'user_id' => $participant->id,
        'visibility' => Thread::VIS_REGISTERED,
    ]);
    $this->assertDatabaseHas('comments', [
        'thread_id' => $threadId,
        'user_id' => $participant->id,
        'parent_id' => null,
        'content' => 'The opening message',
        'depth' => 1,
    ]);
});

it('creates replies and returns comments as a nested tree', function () {
    $author = User::factory()->create();
    $participant = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id, 'is_published' => true]);
    $post = Post::factory()->create(['blog_id' => $blog->id, 'user_id' => $author->id]);
    $thread = Thread::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);
    $rootComment = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $author->id,
        'content' => 'Root comment',
        'depth' => 1,
    ]);

    $createResponse = actingAs($participant)->postJson('/threads/' . $thread->id . '/comments', [
        'parent_id' => $rootComment->id,
        'content' => 'Nested reply',
    ]);

    $createResponse->assertCreated()->assertJsonPath('depth', 2);
    $this->getJson('/threads/' . $thread->id . '/comments')
        ->assertOk()
        ->assertJsonPath('0.content', 'Root comment')
        ->assertJsonPath('0.children.0.content', 'Nested reply')
        ->assertJsonPath('0.children.0.depth', 2);
});

it('rejects mutations when commenting is disabled', function () {
    $author = User::factory()->create();
    $participant = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'user_id' => $author->id,
        'allow_comments' => false,
    ]);

    $response = actingAs($participant)->postJson('/posts/' . $post->id . '/threads', [
        'title' => 'Blocked topic',
        'visibility' => Thread::VIS_PUBLIC,
        'content' => 'Blocked content',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('threads', ['post_id' => $post->id]);
});

it('enforces the configured maximum reply depth', function () {
    $author = User::factory()->create();
    $participant = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'user_id' => $author->id,
        'comments_max_depth' => 1,
    ]);
    $thread = Thread::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);
    $rootComment = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $author->id,
        'depth' => 1,
    ]);

    $response = actingAs($participant)->postJson('/threads/' . $thread->id . '/comments', [
        'parent_id' => $rootComment->id,
        'content' => 'Too deeply nested',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('parent_id');
});

it('rejects comments in locked threads', function () {
    $author = User::factory()->create();
    $participant = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $author->id, 'is_published' => true]);
    $post = Post::factory()->create(['blog_id' => $blog->id, 'user_id' => $author->id]);
    $thread = Thread::factory()->create([
        'post_id' => $post->id,
        'user_id' => $author->id,
        'is_locked' => true,
    ]);

    $response = actingAs($participant)->postJson('/threads/' . $thread->id . '/comments', [
        'content' => 'Blocked reply',
    ]);

    $response->assertForbidden();
});

it('requires group membership to read group threads', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $owner->id]);
    $group->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
    $post = Post::factory()->create([
        'blog_id' => null,
        'group_id' => $group->id,
        'user_id' => $owner->id,
    ]);
    $thread = Thread::factory()->create(['post_id' => $post->id, 'user_id' => $owner->id]);

    $this->getJson('/posts/' . $post->id . '/threads')->assertForbidden();
    actingAs($outsider)->getJson('/posts/' . $post->id . '/threads')->assertForbidden();
    actingAs($member)->getJson('/posts/' . $post->id . '/threads')->assertOk();
    actingAs($member)->postJson('/threads/' . $thread->id . '/comments', [
        'content' => 'Member reply',
    ])->assertCreated();
});
