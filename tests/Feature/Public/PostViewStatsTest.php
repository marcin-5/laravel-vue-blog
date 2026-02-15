<?php

use App\Models\AnonymousView;
use App\Models\Blog;
use App\Models\BotView;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Models\UserAgent;

it('exposes view stats (registered, anonymous, bots) on post page for owner', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $post = Post::factory()->for($blog)->create();

    // Registered
    PageView::query()->create([
        'user_id' => $owner->id,
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
    ]);
    PageView::query()->create([
        'user_id' => User::factory()->create()->id,
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
    ]);

    // Anonymous
    $ua = UserAgent::factory()->create();
    AnonymousView::query()->create([
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 4,
    ]);

    // Bots
    BotView::query()->create([
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 1,
    ]);

    $response = $this->actingAs($owner)->get("/{$blog->slug}/{$post->slug}");

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('public/blog/Post')
        ->where('viewStats.consented', 2)
        ->where('viewStats.anonymous', 4)
        ->where('viewStats.bots', 1),
    );
});

it('exposes view stats (registered, anonymous, bots) on post page for admin', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $post = Post::factory()->for($blog)->create();

    PageView::query()->create([
        'user_id' => $owner->id,
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
    ]);
    $ua = UserAgent::factory()->create();
    AnonymousView::query()->create([
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 2,
    ]);
    BotView::query()->create([
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 3,
    ]);

    $response = $this->actingAs($admin)->get("/{$blog->slug}/{$post->slug}");

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('public/blog/Post')
        ->where('viewStats.consented', 1)
        ->where('viewStats.anonymous', 2)
        ->where('viewStats.bots', 3),
    );
});

it('does not expose view stats on post page to guests or non-owners', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $post = Post::factory()->for($blog)->create();

    // Guest
    $guestResponse = $this->get("/{$blog->slug}/{$post->slug}");
    $guestResponse->assertOk();
    $guestResponse->assertInertia(fn($page) => $page
        ->component('public/blog/Post')
        ->where('viewStats', null),
    );

    // Logged-in but not owner and not admin
    $userResponse = $this->actingAs($other)->get("/{$blog->slug}/{$post->slug}");
    $userResponse->assertOk();
    $userResponse->assertInertia(fn($page) => $page
        ->component('public/blog/Post')
        ->where('viewStats', null),
    );
});
