<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public blog post page sends published_at in Y-m-d format regardless of locale', function () {
    $user = User::factory()->create();

    $blog = Blog::factory()->create([
        'is_published' => true,
        'locale' => 'pl',
        'user_id' => $user->id,
    ]);

    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'user_id' => $user->id,
        'published_at' => '2026-02-05 10:00:00',
        'is_published' => true,
        'slug' => 'test-post',
        'title' => 'Test Post',
    ]);

    $response = $this->get(route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $post->slug]));

    $response->assertInertia(
        fn($page) => $page
            ->component('public/blog/Post')
            ->has('post')
            ->where('post.published_at', '2026-02-05'),
    );
});
