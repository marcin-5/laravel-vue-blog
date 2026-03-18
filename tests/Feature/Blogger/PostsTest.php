<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->blog = Blog::factory()->create(['user_id' => $this->user->id]);
});

it('can create a post with seo_title', function () {
    actingAs($this->user)
        ->post(route('posts.store'), [
            'blog_id' => $this->blog->id,
            'title' => 'New Post Title',
            'seo_title' => 'SEO Optimized Title',
            'content' => 'Post content',
        ])
        ->assertRedirect();

    $post = Post::where('title', 'New Post Title')->first();
    expect($post->seo_title)->toBe('SEO Optimized Title');
});

it('can update a post with seo_title', function () {
    $post = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'title' => 'Original Title',
    ]);

    actingAs($this->user)
        ->patch(route('posts.update', $post), [
            'title' => 'Updated Title',
            'seo_title' => 'Updated SEO Title',
        ])
        ->assertRedirect();

    $post->refresh();
    expect($post->title)
        ->toBe('Updated Title')
        ->and($post->seo_title)->toBe('Updated SEO Title');
});

it('can clear seo_title during update', function () {
    $post = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'seo_title' => 'Initial SEO Title',
    ]);

    actingAs($this->user)
        ->patch(route('posts.update', $post), [
            'seo_title' => null,
        ])
        ->assertRedirect();

    $post->refresh();
    expect($post->seo_title)->toBeNull();
});
