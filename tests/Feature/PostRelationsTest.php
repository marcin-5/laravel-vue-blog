<?php

use App\Models\Blog;
use App\Models\ExternalLink;
use App\Models\Post;
use App\Models\RelatedPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('post can have related posts', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id]);
    $post = Post::factory()->create(['blog_id' => $blog->id, 'user_id' => $user->id]);
    $relatedPost = Post::factory()->create(['blog_id' => $blog->id, 'user_id' => $user->id]);

    RelatedPost::factory()->create([
        'post_id' => $post->id,
        'blog_id' => $blog->id,
        'related_post_id' => $relatedPost->id,
        'reason' => 'Test reason',
    ]);

    expect($post->fresh()->relatedPosts)
        ->toHaveCount(1)
        ->and($post->fresh()->relatedPosts->first()->relatedPost->id)->toBe($relatedPost->id)
        ->and($post->fresh()->relatedPosts->first()->reason)->toBe('Test reason');
});

test('post can have external links', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id]);
    $post = Post::factory()->create(['blog_id' => $blog->id, 'user_id' => $user->id]);

    ExternalLink::factory()->create([
        'post_id' => $post->id,
        'title' => 'Example Link',
        'url' => 'https://example.com',
        'description' => 'External description',
        'reason' => 'External reason',
    ]);

    expect($post->fresh()->externalLinks)
        ->toHaveCount(1)
        ->and($post->fresh()->externalLinks->first()->title)->toBe('Example Link')
        ->and($post->fresh()->externalLinks->first()->url)->toBe('https://example.com')
        ->and($post->fresh()->externalLinks->first()->description)->toBe('External description')
        ->and($post->fresh()->externalLinks->first()->reason)->toBe('External reason');
});
