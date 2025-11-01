<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use App\Services\BlogNavigationService;

uses()->group('navigation');

beforeEach(function () {
    $this->service = new BlogNavigationService;
    $this->user = User::factory()->create();
    $this->blog = Blog::factory()->create(['user_id' => $this->user->id, 'is_published' => true]);
});

test('getPostNavigation returns correct previous post', function () {
    $post1 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(1),
        'is_published' => true,
    ]);

    $post2 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(2),
        'is_published' => true,
    ]);

    $post3 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(3),
        'is_published' => true,
    ]);

    // From post3, previous should be post2
    $navigation = $this->service->getPostNavigation($this->blog, $post3);
    expect($navigation['prevPost'])->not->toBeNull()
        ->and($navigation['prevPost']['slug'])->toBe($post2->slug);
});

test('getPostNavigation returns correct next post', function () {
    $post1 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(1),
        'is_published' => true,
    ]);

    $post2 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(2),
        'is_published' => true,
    ]);

    $post3 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(3),
        'is_published' => true,
    ]);

    // From post2, next should be post3
    $navigation = $this->service->getPostNavigation($this->blog, $post2);
    expect($navigation['nextPost'])->not->toBeNull()
        ->and($navigation['nextPost']['slug'])->toBe($post3->slug);
});

test('clicking next twice then previous returns to second post', function () {
    $post1 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'title' => 'First Post',
        'slug' => 'first-post',
        'published_at' => now()->subDays(1),
        'is_published' => true,
    ]);

    $post2 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'title' => 'Second Post',
        'slug' => 'second-post',
        'published_at' => now()->subDays(2),
        'is_published' => true,
    ]);

    $post3 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'title' => 'Third Post',
        'slug' => 'third-post',
        'published_at' => now()->subDays(3),
        'is_published' => true,
    ]);

    // Start at post1, click next to get post2
    $nav1 = $this->service->getPostNavigation($this->blog, $post1);
    expect($nav1['nextPost']['slug'])->toBe('second-post');

    // Click next again to get post3
    $nav2 = $this->service->getPostNavigation($this->blog, $post2);
    expect($nav2['nextPost']['slug'])->toBe('third-post');

    // Now from post3, click previous should return to post2, not post1
    $nav3 = $this->service->getPostNavigation($this->blog, $post3);
    expect($nav3['prevPost']['slug'])->toBe('second-post');
});

test('getPostNavigation returns null for previous when at oldest post', function () {
    $post1 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(1),
        'is_published' => true,
    ]);

    $post2 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(2),
        'is_published' => true,
    ]);

    // From post1 (newest), previous should be null
    $navigation = $this->service->getPostNavigation($this->blog, $post1);
    expect($navigation['prevPost'])->toBeNull();
});

test('getPostNavigation returns null for next when at newest post', function () {
    $post1 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(1),
        'is_published' => true,
    ]);

    $post2 = Post::factory()->create([
        'blog_id' => $this->blog->id,
        'published_at' => now()->subDays(2),
        'is_published' => true,
    ]);

    // From post2 (oldest), next should be null
    $navigation = $this->service->getPostNavigation($this->blog, $post2);
    expect($navigation['nextPost'])->toBeNull();
});
