<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->blog = Blog::factory()->create(['user_id' => $this->user->id]);
});

it('clears redundant fields when storing a post as extension', function () {
    $relatedPost = Post::factory()->create(['blog_id' => $this->blog->id]);

    $response = actingAs($this->user)
        ->post(route('posts.store'), [
            'blog_id' => $this->blog->id,
            'title' => 'Extension Post',
            'visibility' => 'extension',
            'seo_title' => 'Should be cleared',
            'excerpt' => 'Should be cleared',
            'summary' => 'Should be cleared',
            'content' => 'Keep this',
            'related_posts' => [
                ['blog_id' => $this->blog->id, 'related_post_id' => $relatedPost->id, 'reason' => 'Should be cleared'],
            ],
            'external_links' => [
                ['title' => 'Google', 'url' => 'https://google.com', 'reason' => 'Should be cleared'],
            ],
        ]);

    $response->assertRedirect();

    $post = Post::where('title', 'Extension Post')->first();

    expect($post->visibility)
        ->toBe('extension')
        ->and($post->seo_title)->toBeEmpty()
        ->and($post->excerpt)->toBeEmpty()
        ->and($post->summary)->toBeEmpty()
        ->and($post->content)->toBe('Keep this')
        ->and($post->relatedPosts)->toHaveCount(0)
        ->and($post->externalLinks)->toHaveCount(0);
});

it('clears redundant fields when updating a post to extension', function () {
    $post = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'visibility' => 'public',
        'seo_title' => 'Original SEO',
        'excerpt' => 'Original Excerpt',
        'summary' => 'Original Summary',
    ]);

    $post->relatedPosts()->create([
        'blog_id' => $this->blog->id,
        'related_post_id' => Post::factory()->create(['blog_id' => $this->blog->id])->id,
        'reason' => 'test',
    ]);

    $post->externalLinks()->create([
        'title' => 'Link',
        'url' => 'https://example.com',
    ]);

    actingAs($this->user)
        ->patch(route('posts.update', $post), [
            'visibility' => 'extension',
            'seo_title' => 'Even if sent, should be cleared',
            'excerpt' => 'Even if sent, should be cleared',
            'summary' => 'Even if sent, should be cleared',
        ])
        ->assertRedirect();

    $post->refresh();

    expect($post->visibility)
        ->toBe('extension')
        ->and($post->seo_title)->toBeEmpty()
        ->and($post->excerpt)->toBeEmpty()
        ->and($post->summary)->toBeEmpty()
        ->and($post->relatedPosts)->toHaveCount(0)
        ->and($post->externalLinks)->toHaveCount(0);
});
