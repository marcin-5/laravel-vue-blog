<?php

use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Config::set('app.domain', 'osobliwy.pl');
    Config::set('debugbar.enabled', true);
});

test('debugbar assets on subdomain are not intercepted by blog post route', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'test-blog',
        'is_published' => true,
    ]);

    // We expect this to NOT hit PublicBlogController::post
    // Even if debugbar is not fully functional in tests, it should at least match the debugbar route
    $response = $this->get('http://test-blog.osobliwy.pl/_debugbar/assets?type=js');

    // If it was intercepted by PublicBlogController::post, it would probably return a 404 (post not found)
    // or if the blog is not found, a 404.
    // But here we want to see if it matches the debugbar route.

    // Check which route was used
    try {
        $route = Route::getRoutes()->match(request()->create('http://test-blog.osobliwy.pl/_debugbar/assets', 'GET'));
        $routeName = $route->getName();
    } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
        $routeName = null;
    }

    expect($routeName)->not->toBe('blog.public.post');
});

test('normal post on subdomain is still intercepted by blog post route', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'slug' => 'test-blog',
        'is_published' => true,
    ]);

    $route = Route::getRoutes()->match(request()->create('http://test-blog.osobliwy.pl/some-real-post', 'GET'));

    expect($route->getName())->toBe('blog.public.post');
});
