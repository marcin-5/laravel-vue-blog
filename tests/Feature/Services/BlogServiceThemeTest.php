<?php

use App\Models\Blog;
use App\Models\User;
use App\Services\BlogService;

it('returns blogs with theme colors in getUserBlogs', function () {
    $user = User::factory()->create();
    $themeData = [
        'light' => ['--background' => '#ffffff'],
        'dark' => ['--background' => '#000000'],
    ];

    Blog::factory()->create([
        'user_id' => $user->id,
        'theme' => $themeData,
    ]);

    $service = app(BlogService::class);
    $blogs = $service->getUserBlogs($user);

    expect($blogs)->toHaveCount(1);
    $blog = $blogs->first();

    expect($blog->theme)->toBe($themeData);
});
