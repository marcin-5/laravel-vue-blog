<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it redirects old /blogs/slug to public /slug with 301', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create([
        'slug' => 'moj-blog',
        'is_published' => true,
        'locale' => 'pl',
    ]);

    $response = $this->get('/blogs/moj-blog');

    $response->assertRedirect(getBlogUrl($blog));
    $response->assertStatus(301);
});

test('it redirects old /blogs/blog-slug/post-slug to public /blog-slug/post-slug with 301', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create([
        'slug' => 'moj-blog',
        'is_published' => true,
        'locale' => 'pl',
    ]);
    $post = Post::factory()->create(['blog_id' => $blog->id, 'slug' => 'moj-post']);

    $response = $this->get('/blogs/moj-blog/moj-post');

    $response->assertRedirect(getBlogUrl($blog, '/moj-post'));
    $response->assertStatus(301);
});

test('dashboard and blogs panel have noindex header', function () {
    $user = User::factory()->create();

    $routes = ['/dashboard', '/blogs'];

    foreach ($routes as $path) {
        $response = $this->actingAs($user)->get($path);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
});

test('public routes do not have noindex header', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create([
        'slug' => 'public-blog',
        'is_published' => true,
        'locale' => app()->getLocale(),
    ]);

    $routes = ['/', '/about', '/contact', getBlogUrl($blog)];

    foreach ($routes as $path) {
        $response = $this->get($path);
        $response->assertHeaderMissing('X-Robots-Tag');
    }
});
