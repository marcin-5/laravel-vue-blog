<?php

use App\Models\Post;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('displays public posts in landing page', function () {
    $blog = createBlog(['is_published' => true]);
    $post = createPost($blog, [
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);

    get(route('blog.public.landing', $blog->slug))
        ->assertSuccessful()
        ->assertSee($post->title);
});

it('does not display unlisted posts in landing page', function () {
    $blog = createBlog(['is_published' => true]);
    $unlistedPost = createPost($blog, [
        'visibility' => Post::VIS_UNLISTED,
        'published_at' => now()->subDay(),
    ]);

    get(route('blog.public.landing', $blog->slug))
        ->assertSuccessful()
        ->assertDontSee($unlistedPost->title);
});

it('allows viewing unlisted post via direct link', function () {
    $blog = createBlog(['is_published' => true]);
    $unlistedPost = createPost($blog, [
        'visibility' => Post::VIS_UNLISTED,
        'published_at' => now()->subDay(),
    ]);

    get(route('blog.public.post', [$blog->slug, $unlistedPost->slug]))
        ->assertSuccessful()
        ->assertSee($unlistedPost->title);
});

it('allows blogger to save post as unlisted', function () {
    $user = createUser([
        'email_verified_at' => now(),
    ]);
    $blog = createBlog([], $user);

    actingAs($user)
        ->post(route('posts.store'), [
            'blog_id' => $blog->id,
            'title' => 'Unlisted Post',
            'content' => 'Post content',
            'visibility' => Post::VIS_UNLISTED,
            'is_published' => true,
        ])
        ->assertRedirect();

    $post = Post::where('title', 'Unlisted Post')->first();
    expect($post->visibility)->toBe(Post::VIS_UNLISTED);
});
