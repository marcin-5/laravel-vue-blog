<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use App\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates sitemap with home page, published blogs, and public published posts from published blogs', function () {
    $user = User::factory()->create();

    $publishedBlog = Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'Published Blog',
        'slug' => 'published-blog',
        'is_published' => true,
        'locale' => config('app.locale'),
    ]);

    Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'Unpublished Blog',
        'slug' => 'unpublished-blog',
        'is_published' => false,
        'locale' => config('app.locale'),
    ]);

    $publishedPublicPost = Post::factory()->create([
        'blog_id' => $publishedBlog->id,
        'user_id' => $user->id,
        'title' => 'Published Public Post',
        'slug' => 'published-public-post',
        'is_published' => true,
        'visibility' => 'public',
        'published_at' => now()->subDay(),
    ]);

    Post::factory()->create([
        'blog_id' => $publishedBlog->id,
        'user_id' => $user->id,
        'title' => 'Unpublished Post',
        'slug' => 'unpublished-post',
        'is_published' => false,
        'published_at' => null,
    ]);

    Post::factory()->create([
        'blog_id' => $publishedBlog->id,
        'user_id' => $user->id,
        'title' => 'Published Private Post',
        'slug' => 'published-private-post',
        'is_published' => true,
        'visibility' => 'private',
        'published_at' => now()->subDay(),
    ]);

    $sitemapService = app(SitemapService::class);
    $xmlMain = $sitemapService->getSitemap(config('app.locale'));

    expect($xmlMain)->toContain('/')
        ->and($xmlMain)->toContain('published-blog')
        ->and($xmlMain)->not->toContain('published-public-post')
        ->and($xmlMain)->not->toContain('unpublished-blog');

    $xmlBlog = $sitemapService->getSitemap(config('app.locale'), $publishedBlog);

    expect($xmlBlog)->toContain('published-blog')
        ->and($xmlBlog)->toContain('published-public-post')
        ->and($xmlBlog)->not->toContain('unpublished-post')
        ->and($xmlBlog)->not->toContain('published-private-post');
});
