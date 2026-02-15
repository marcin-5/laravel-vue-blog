<?php

use App\Models\AnonymousView;
use App\Models\Blog;
use App\Models\BotView;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Models\UserAgent;

it('exposes view stats (registered, anonymous, bots) on blog landing for owner', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    Post::factory()->for($blog)->create();

    // Registered (page views)
    PageView::query()->create([
        'user_id' => $owner->id,
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
    ]);
    PageView::query()->create([
        'user_id' => User::factory()->create()->id,
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
    ]);

    // Anonymous views (sum of hits)
    $ua = UserAgent::factory()->create();
    AnonymousView::query()->create([
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 3,
    ]);

    // Bot views (sum of hits)
    BotView::query()->create([
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 2,
    ]);

    $response = $this->actingAs($owner)->get("/{$blog->slug}");

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('public/blog/Landing')
        ->where('viewStats.consented', 2)
        ->where('viewStats.anonymous', 3)
        ->where('viewStats.bots', 2),
    );
});

it('exposes view stats (registered, anonymous, bots) on blog landing for admin', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);

    PageView::query()->create([
        'user_id' => $owner->id,
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
    ]);
    $ua = UserAgent::factory()->create();
    AnonymousView::query()->create([
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 1,
    ]);
    BotView::query()->create([
        'viewable_type' => Blog::class,
        'viewable_id' => $blog->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 1,
    ]);

    $response = $this->actingAs($admin)->get("/{$blog->slug}");

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('public/blog/Landing')
        ->where('viewStats.consented', 1)
        ->where('viewStats.anonymous', 1)
        ->where('viewStats.bots', 1),
    );
});

it('does not expose view stats on blog landing to guests or non-owners', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);

    // Guest
    $guestResponse = $this->get("/{$blog->slug}");
    $guestResponse->assertOk();
    $guestResponse->assertInertia(fn($page) => $page
        ->component('public/blog/Landing')
        ->where('viewStats', null),
    );

    // Logged-in but not owner and not admin
    $userResponse = $this->actingAs($other)->get("/{$blog->slug}");
    $userResponse->assertOk();
    $userResponse->assertInertia(fn($page) => $page
        ->component('public/blog/Landing')
        ->where('viewStats', null),
    );
});
