<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('SeoService generates correct blog URL without /blogs/ prefix', function () {
    $seoService = new SeoService;
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create(['slug' => 'my-blog']);

    $baseUrl = 'https://example.com';
    $structuredData = $seoService->generateBlogStructuredData($blog, [], $baseUrl, 'Description');

    expect($structuredData['url'])->toBe('https://example.com/' . $blog->slug);
});

test('SeoService generates correct post URL without /blogs/ prefix', function () {
    $seoService = new SeoService;
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create(['slug' => 'my-blog']);
    $post = Post::factory()->create(['blog_id' => $blog->id, 'slug' => 'my-post']);

    $baseUrl = 'https://example.com';
    $structuredData = $seoService->generatePostStructuredData($blog, $post, $baseUrl, 'Description');

    expect($structuredData['url'])->toBe('https://example.com/' . $blog->slug . '/' . $post->slug);
});

test('SeoService generateHomeStructuredData generates correct blog URLs without /blogs/ prefix', function () {
    $seoService = new SeoService;
    $baseUrl = 'https://example.com';
    $blogs = [
        [
            'name' => 'Blog 1',
            'author' => 'Author 1',
            'slug' => 'blog-1',
            'descriptionHtml' => 'Description 1',
        ],
    ];

    $structuredData = $seoService->generateHomeStructuredData($blogs, 'Home', 'Home Description', $baseUrl);

    expect($structuredData['blogPost'][0]['url'])->toBe('https://example.com/blog-1');
});
