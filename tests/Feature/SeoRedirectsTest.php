<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;

test('it redirects old /blogs/slug to public /slug with 301', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create(['slug' => 'moj-blog', 'is_published' => true]);

    $response = $this->get('/blogs/moj-blog');

    $response->assertRedirect('/moj-blog');
    $response->assertStatus(301);
});

test('it redirects old /blogs/blog-slug/post-slug to public /blog-slug/post-slug with 301', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create(['slug' => 'moj-blog', 'is_published' => true]);
    $post = Post::factory()->create(['blog_id' => $blog->id, 'slug' => 'moj-post']);

    $response = $this->get('/blogs/moj-blog/moj-post');

    $response->assertRedirect('/moj-blog/moj-post');
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
    $blog = Blog::factory()->for($user)->create(['slug' => 'public-blog', 'is_published' => true]);

    $routes = ['/', '/about', '/contact', '/public-blog'];

    foreach ($routes as $path) {
        $response = $this->get($path);
        $response->assertHeaderMissing('X-Robots-Tag');
    }
});
