<?php

namespace Tests\Feature\Queries;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Queries\Public\WelcomeQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::clear();
});

it('fetches welcome blogs and categories', function () {
    $user = User::factory()->create(['name' => 'Author Name']);
    $category = Category::factory()->create(['name' => ['en' => 'Category 1'], 'slug' => 'cat-1']);

    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'Blog 1',
        'is_published' => true,
        'locale' => 'en',
    ]);
    $blog->categories()->attach($category);

    // Add a published post to set latest_post_at
    Post::factory()->create([
        'blog_id' => $blog->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $request = Request::create('/', 'GET');
    $query = new WelcomeQuery();
    $result = $query->handle($request);

    expect($result)->toHaveKeys(['blogs', 'categories', 'selectedCategoryIds', 'locale'])
        ->and($result['blogs'])->toHaveCount(1)
        ->and($result['blogs'][0]['name'])->toBe('Blog 1')
        ->and($result['blogs'][0]['author'])->toBe('Author Name')
        ->and($result['categories'])->toHaveCount(1)
        ->and($result['categories'][0]['name'])->toBe('Category 1');
});

it('filters blogs by categories', function () {
    $category1 = Category::factory()->create(['name' => ['en' => 'Cat 1']]);
    $category2 = Category::factory()->create(['name' => ['en' => 'Cat 2']]);

    $blog1 = Blog::factory()->create(['is_published' => true, 'name' => 'Blog 1', 'user_id' => User::factory()]);
    $blog1->categories()->attach($category1);

    $blog2 = Blog::factory()->create(['is_published' => true, 'name' => 'Blog 2', 'user_id' => User::factory()]);
    $blog2->categories()->attach($category2);

    // Request with category1
    $request = Request::create('/', 'GET', ['categories' => (string)$category1->id]);
    $query = new WelcomeQuery();
    $result = $query->handle($request);

    expect($result['blogs'])->toHaveCount(1)
        ->and($result['blogs'][0]['name'])->toBe('Blog 1')
        ->and($result['selectedCategoryIds'])->toBe([$category1->id]);
});

it('orders blogs by latest_post_at', function () {
    $blog1 = Blog::factory()->create(['is_published' => true, 'name' => 'Oldest Blog', 'user_id' => User::factory()]);
    $blog2 = Blog::factory()->create(['is_published' => true, 'name' => 'Newest Blog', 'user_id' => User::factory()]);

    Post::factory()->create([
        'blog_id' => $blog1->id,
        'is_published' => true,
        'published_at' => now()->subDays(10),
    ]);

    Post::factory()->create([
        'blog_id' => $blog2->id,
        'is_published' => true,
        'published_at' => now(),
    ]);

    $request = Request::create('/', 'GET');
    $query = new WelcomeQuery();
    $result = $query->handle($request);

    expect($result['blogs'])->toHaveCount(2)
        ->and($result['blogs'][0]['name'])->toBe('Newest Blog')
        ->and($result['blogs'][1]['name'])->toBe('Oldest Blog');
});
