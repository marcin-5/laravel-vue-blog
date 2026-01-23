<?php

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores the author when creating a post through PostService', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $user->id]);
    $postService = app(PostService::class);

    $post = $postService->createPost($blog, [
        'title' => 'Test Post',
        'content' => 'Content',
    ], $user->id);

    expect($post->user_id)->toBe($user->id)
        ->and($post->user->id)->toBe($user->id);
});

it('uses fallback author in GroupController when post user_id is null', function () {
    $groupOwner = User::factory()->create(['name' => 'Group Owner']);
    $group = Group::factory()->create(['user_id' => $groupOwner->id]);
    $post = Post::factory()->create([
        'group_id' => $group->id,
        'blog_id' => null,
        'user_id' => null, // Legacy post
        'published_at' => now()->subDay(),
        'is_published' => true,
    ]);

    $response = $this->actingAs($groupOwner)->get("/_/{$group->slug}/{$post->slug}");

    $response->assertInertia(fn($page) => $page
        ->where('post.author', 'Group Owner')
        ->where('post.author_email', $groupOwner->email),
    );
});

it('uses post author in GroupController when post user_id is set', function () {
    $groupOwner = User::factory()->create(['name' => 'Group Owner']);
    $contributor = User::factory()->create(['name' => 'Contributor']);
    $group = Group::factory()->create(['user_id' => $groupOwner->id]);
    $post = Post::factory()->create([
        'group_id' => $group->id,
        'blog_id' => null,
        'user_id' => $contributor->id,
        'published_at' => now()->subDay(),
        'is_published' => true,
    ]);

    $response = $this->actingAs($groupOwner)->get("/_/{$group->slug}/{$post->slug}");

    $response->assertInertia(fn($page) => $page
        ->where('post.author', 'Contributor')
        ->where('post.author_email', $contributor->email),
    );
});

it('uses fallback author in PublicBlogController when post user_id is null', function () {
    $blogOwner = User::factory()->create(['name' => 'Blog Owner']);
    $blog = Blog::factory()->create(['user_id' => $blogOwner->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'user_id' => null, // Legacy post
        'is_published' => true,
        'visibility' => 'public',
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get("/{$blog->slug}/{$post->slug}");

    $response->assertInertia(fn($page) => $page
        ->where('post.author', 'Blog Owner')
        ->where('post.author_email', $blogOwner->email),
    );
});
