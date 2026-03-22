<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia as Assert;

it('returns 404 for unpublished blog on landing page', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => false]);

    $response = $this->get("/{$blog->slug}");

    $response->assertNotFound();
});

it('returns 200 for published blog on landing page and sets locale', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'locale' => 'pl',
    ]);

    $response = $this->get("/{$blog->slug}");

    $response->assertSuccessful();
    expect(App::getLocale())->toBe('pl');

    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/blog/Landing')
        ->has('blog')
        ->has('posts')
        ->has('seo')
        ->where('locale', 'pl'),
    );
});

it('uses seo_title for blog landing when available and falls back to name', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'name' => 'My Blog',
        'seo_title' => 'My Awesome SEO Blog',
    ]);

    // With seo_title set
    $this
        ->get("/{$blog->slug}")
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.title', 'My Awesome SEO Blog'),
        );

    // Without seo_title (fallback to name)
    $blog->update(['seo_title' => null]);

    $this
        ->get("/{$blog->slug}")
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.title', 'My Blog - ' . config('app.name')),
        );
});

it('returns 404 for unpublished blog on post page', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => false]);
    $post = Post::factory()->for($blog)->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
        'visibility' => Post::VIS_PUBLIC,
    ]);

    $response = $this->get("/{$blog->slug}/{$post->slug}");

    $response->assertNotFound();
});

it('returns 404 for unpublished post on public blog', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $post = Post::factory()->for($blog)->create([
        'is_published' => false,
        'published_at' => null,
    ]);

    $response = $this->get("/{$blog->slug}/{$post->slug}");

    $response->assertNotFound();
});

it('returns 200 for published post on published blog and checks sidebar position', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'sidebar' => -1, // SIDEBAR_LEFT
        'locale' => 'en',
    ]);

    $post = Post::factory()->for($blog)->create([
        'is_published' => true,
        'published_at' => now()->subDay(),
        'visibility' => Post::VIS_PUBLIC,
    ]);

    $response = $this->get("/{$blog->slug}/{$post->slug}");

    $response->assertSuccessful();
    expect(App::getLocale())->toBe('en');

    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/blog/Post')
        ->has('blog')
        ->has('post')
        ->has('seo')
        ->where('sidebarPosition', 'left'),
    );
});

it('uses seo_title for post when available and falls back to title', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true, 'name' => 'BlogName']);

    $post = Post::factory()->for($blog)->create([
        'title' => 'Original Title',
        'seo_title' => 'SEO Optimized Title',
        'is_published' => true,
        'published_at' => now()->subDay(),
        'visibility' => Post::VIS_PUBLIC,
    ]);

    // With seo_title set
    $this
        ->get("/{$blog->slug}/{$post->slug}")
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.title', 'SEO Optimized Title'),
        );

    // Without seo_title (fallback to title)
    $post->update(['seo_title' => null]);

    $this
        ->get("/{$blog->slug}/{$post->slug}")
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.title', 'Original Title - ' . $blog->name),
        );
});
