<?php

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->blog = Blog::factory()->create(['user_id' => $this->user->id]);
});

it('creates a post with default comment settings', function () {
    actingAs($this->user)
        ->post(route('posts.store'), [
            'blog_id' => $this->blog->id,
            'title' => 'Post with Default Comments',
            'content' => 'Sample content',
        ])
        ->assertRedirect();

    $post = Post::where('title', 'Post with Default Comments')->first();
    expect($post->allow_comments)->toBeTrue()
        ->and($post->comments_max_depth)->toBe(5);
});

it('creates a post with custom comment settings', function () {
    actingAs($this->user)
        ->post(route('posts.store'), [
            'blog_id' => $this->blog->id,
            'title' => 'Post with Custom Comments',
            'content' => 'Sample content',
            'allow_comments' => false,
            'comments_max_depth' => 2,
        ])
        ->assertRedirect();

    $post = Post::where('title', 'Post with Custom Comments')->first();
    expect($post->allow_comments)->toBeFalse()
        ->and($post->comments_max_depth)->toBe(2);
});

it('allows comments_max_depth to be 0 for unlimited nesting', function () {
    actingAs($this->user)
        ->post(route('posts.store'), [
            'blog_id' => $this->blog->id,
            'title' => 'Post with Unlimited Comments',
            'content' => 'Sample content',
            'allow_comments' => true,
            'comments_max_depth' => 0,
        ])
        ->assertRedirect();

    $post = Post::where('title', 'Post with Unlimited Comments')->first();
    expect($post->comments_max_depth)->toBe(0);
});

it('updates post comment settings', function () {
    $post = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'user_id' => $this->user->id,
        'allow_comments' => true,
        'comments_max_depth' => 5,
    ]);

    actingAs($this->user)
        ->patch(route('posts.update', $post), [
            'allow_comments' => false,
            'comments_max_depth' => 8,
        ])
        ->assertRedirect();

    $post->refresh();
    expect($post->allow_comments)->toBeFalse()
        ->and($post->comments_max_depth)->toBe(8);
});

it('validates thread and comment model relationships', function () {
    $post = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'user_id' => $this->user->id,
    ]);

    $thread = Thread::factory()->create([
        'post_id' => $post->id,
        'user_id' => $this->user->id,
        'title' => 'Discussion topic',
    ]);

    $rootComment = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $this->user->id,
        'parent_id' => null,
        'content' => 'First message',
        'depth' => 1,
    ]);

    $replyComment = Comment::factory()->create([
        'thread_id' => $thread->id,
        'user_id' => $this->user->id,
        'parent_id' => $rootComment->id,
        'content' => 'Reply message',
        'depth' => 2,
    ]);

    expect($post->threads)->toHaveCount(1)
        ->and($post->threads->first()->id)->toBe($thread->id)
        ->and($thread->post->id)->toBe($post->id)
        ->and($thread->user->id)->toBe($this->user->id)
        ->and($thread->comments)->toHaveCount(2)
        ->and($thread->rootComments)->toHaveCount(1)
        ->and($rootComment->children)->toHaveCount(1)
        ->and($rootComment->children->first()->id)->toBe($replyComment->id)
        ->and($replyComment->parent->id)->toBe($rootComment->id);
});
