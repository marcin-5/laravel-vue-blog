<?php

use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;

it('exposes unique view count for post page via Inertia props', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $post = Post::factory()->for($blog)->create();

    $morphPost = (new Post)->getMorphClass();

    // same logged-in user views the post twice
    $viewer = User::factory()->create();
    PageView::query()->create([
        'user_id' => $viewer->id,
        'viewable_type' => $morphPost,
        'viewable_id' => $post->id,
    ]);
    PageView::query()->create([
        'user_id' => $viewer->id,
        'viewable_type' => $morphPost,
        'viewable_id' => $post->id,
    ]);

    // anonymous visitor
    PageView::query()->create([
        'visitor_id' => 'cookie-xyz',
        'viewable_type' => $morphPost,
        'viewable_id' => $post->id,
    ]);

    $response = $this->get("/{$blog->slug}/{$post->slug}");

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('public/blog/Post')
        ->where('viewStats.unique', 2), // 1 logged-in + 1 anon
    );
});
