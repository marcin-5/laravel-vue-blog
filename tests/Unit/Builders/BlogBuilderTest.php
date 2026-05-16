<?php

declare(strict_types=1);

use App\Models\Blog;
use App\Models\Category;
use App\Models\Post;

it('eager loads posts for index', function () {
    $blog = createBlog();
    Post::factory()->count(2)->create(['blog_id' => $blog->id, 'is_published' => true]);

    $result = Blog::query()->withPostsForIndex()->find($blog->id);

    expect($result->posts)->toHaveCount(2);
});

it('eager loads categories', function () {
    $blog = createBlog();
    $category = Category::factory()->create();
    $blog->categories()->attach($category);

    $result = Blog::query()->withCategories()->find($blog->id);

    expect($result->categories)->toHaveCount(1)
        ->and($result->categories->first()->name)->not->toBeNull();
});

it('orders blogs by latest post', function () {
    $blog1 = createBlog(['name' => 'Blog 1']);
    $blog2 = createBlog(['name' => 'Blog 2']);

    // Blog 2 has more recent post
    Post::factory()->create(['blog_id' => $blog1->id, 'published_at' => now()->subDays(5), 'is_published' => true]);
    Post::factory()->create(['blog_id' => $blog2->id, 'published_at' => now()->subDay(), 'is_published' => true]);

    $results = Blog::query()->orderByLatestPost()->get();

    expect($results->first()->id)->toBe($blog2->id);
});
