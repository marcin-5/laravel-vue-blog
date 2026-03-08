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
    ]);

    Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'Unpublished Blog',
        'slug' => 'unpublished-blog',
        'is_published' => false,
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

    app(SitemapService::class)->generate();

    $sitemapXml = file_get_contents(public_path('sitemap.xml'));

    expect($sitemapXml)->toContain('/')
        ->and($sitemapXml)->toContain('published-blog')
        ->and($sitemapXml)->toContain('published-public-post')
        ->and($sitemapXml)->not->toContain('unpublished-blog')
        ->and($sitemapXml)->not->toContain('unpublished-post')
        ->and($sitemapXml)->not->toContain('published-private-post');
});
