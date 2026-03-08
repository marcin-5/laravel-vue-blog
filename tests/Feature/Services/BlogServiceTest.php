<?php

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;
use App\Services\BlogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('getCategories returns categories sorted by slug with only id and name', function () {
    $catApple = Category::factory()->create(['slug' => 'apple', 'name' => ['en' => 'Apple']]);
    $catBanana = Category::factory()->create(['slug' => 'banana', 'name' => ['en' => 'Banana']]);

    $service = app(BlogService::class);
    $categories = $service->getCategories();

    expect($categories)->toHaveCount(2)
        ->and($categories[0]->id)->toBe($catApple->id)
        ->and($categories[1]->id)->toBe($catBanana->id)
        ->and(array_keys($categories[0]->getAttributes()))->toBe(['id', 'name']);
});

it(
    'getUserBlogs returns only user\'s blogs with specific fields and relations ordered by created_at desc',
    function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $blogOld = Blog::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(2)]);
        $blogNew = Blog::factory()->create(['user_id' => $user->id]);
        Blog::factory()->create(['user_id' => $otherUser->id]);

        $service = app(BlogService::class);
        $blogs = $service->getUserBlogs($user);

        expect($blogs)->toHaveCount(2)
            ->and($blogs->first()->id)->toBe($blogNew->id)
            ->and($blogs->last()->id)->toBe($blogOld->id)
            ->and($blogs->first()->relationLoaded('landingPage'))->toBeTrue()
            ->and($blogs->first()->relationLoaded('categories'))->toBeTrue()
            ->and($blogs->first()->updated_at)->toBeNull();
    },
);

it('createBlog creates blog without syncing categories when none provided', function () {
    $user = User::factory()->create();
    $data = Blog::factory()->for($user)->make()->toArray();
    $service = app(BlogService::class);
    $blog = $service->createBlog($data, []);

    expect($blog->fresh())->toBeInstanceOf(Blog::class)
        ->and($blog->categories)->toBeEmpty();
});

it('createBlog creates blog and syncs provided categories', function () {
    $user = User::factory()->create();
    $data = Blog::factory()->for($user)->make()->toArray();
    $categories = Category::factory(2)->create()->pluck('id')->toArray();

    $service = app(BlogService::class);
    $blog = $service->createBlog($data, $categories);

    expect($blog->fresh()->categories->pluck('id')->toArray())->toBe($categories);
});

it('updateBlog updates blog fields, extracts landing_content, syncs categories', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create();
    $data = [
        'name' => 'New Name',
        'landing_content' => '<p>New landing content</p>',
    ];
    $categoryId = Category::factory()->create()->id;

    $service = app(BlogService::class);
    $service->updateBlog($blog, $data, [$categoryId]);

    $blog = $blog->fresh();
    expect($blog->name)->toBe('New Name')
        ->and($blog->landingPage->content)->toBe('<p>New landing content</p>')
        ->and($blog->categories->first()->id)->toBe($categoryId);
});

it('updateBlog updates blog without landing_content and clears categories if empty array', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create();
    $categoryId = Category::factory()->create()->id;
    $blog->categories()->attach($categoryId);

    $data = ['name' => 'New Name'];

    $service = app(BlogService::class);
    $service->updateBlog($blog, $data, []);

    $blog = $blog->fresh();
    expect($blog->name)->toBe('New Name')
        ->and($blog->landingPage)->toBeNull()
        ->and($blog->categories)->toBeEmpty();
});
