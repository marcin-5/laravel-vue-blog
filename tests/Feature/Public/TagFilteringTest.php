<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lists only posts assigned to the given tag within the blog', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);

    $posts = Post::factory()->count(3)->for($blog)->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);

    /** @var Tag $tag */
    $tag = Tag::factory()->for($blog)->create(['name' => 'Laravel']);

    // Attach tag to first two posts
    $posts[0]->tags()->attach($tag->id);
    $posts[1]->tags()->attach($tag->id);

    $response = $this->get("/$blog->slug/tags/$tag->slug");

    $response->assertOk();
    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/blog/Landing')
        ->has('activeTag', fn(Assert $a) => $a
            ->where('name', 'Laravel')
            ->etc(),
        )
        ->has('posts', 2)
        ->has('pagination'),
    );
});

it('returns 404 when tag does not belong to the blog', function () {
    $owner = User::factory()->create();
    $blogA = Blog::factory()->for($owner)->create(['is_published' => true]);
    $blogB = Blog::factory()->for($owner)->create(['is_published' => true]);

    $tagB = Tag::factory()->for($blogB)->create();

    $this
        ->get("/$blogA->slug/tags/$tagB->slug")
        ->assertNotFound();
});
