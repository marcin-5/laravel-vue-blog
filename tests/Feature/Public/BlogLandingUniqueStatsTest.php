<?php

use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;

it('exposes unique view count for blog landing via Inertia props', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $post = Post::factory()->for($blog)->create();

    // two views by same logged in user of the blog page
    $morphBlog = (new Blog)->getMorphClass();
    PageView::query()->create([
        'user_id' => $owner->id,
        'viewable_type' => $morphBlog,
        'viewable_id' => $blog->id,
    ]);
    PageView::query()->create([
        'user_id' => $owner->id,
        'viewable_type' => $morphBlog,
        'viewable_id' => $blog->id,
    ]);

    // anonymous visitor
    PageView::query()->create([
        'visitor_id' => 'cookie-1',
        'viewable_type' => $morphBlog,
        'viewable_id' => $blog->id,
    ]);

    $response = $this->get("/{$blog->slug}");

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('Blog/Landing')
        ->where('viewStats.unique', 2), // 1 logged-in + 1 anon
    );
});
