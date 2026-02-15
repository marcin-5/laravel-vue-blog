<?php

use App\Models\AnonymousView;
use App\Models\BotView;
use App\Models\Group;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Models\UserAgent;

it('exposes view stats on group landing for owner', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner)->create();

    // Registered
    PageView::query()->create([
        'user_id' => $owner->id,
        'viewable_type' => Group::class,
        'viewable_id' => $group->id,
    ]);
    PageView::query()->create([
        'user_id' => User::factory()->create()->id,
        'viewable_type' => Group::class,
        'viewable_id' => $group->id,
    ]);

    // Anonymous
    $ua = UserAgent::factory()->create();
    AnonymousView::query()->create([
        'viewable_type' => Group::class,
        'viewable_id' => $group->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 5,
    ]);

    // Bots
    BotView::query()->create([
        'viewable_type' => Group::class,
        'viewable_id' => $group->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 2,
    ]);

    $response = $this->actingAs($owner)->get("/_/{$group->slug}");

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('app/group/Landing')
        ->where('viewStats.consented', 2)
        ->where('viewStats.anonymous', 0)
        ->where('viewStats.bots', 0),
    );
});

it('exposes only registered view stats on group post for owner', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->for($owner)->create();
    $post = Post::factory()->for($group)->create();

    // Registered
    PageView::query()->create([
        'user_id' => $owner->id,
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
    ]);

    // Anonymous (should be ignored for groups)
    $ua = UserAgent::factory()->create();
    AnonymousView::query()->create([
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 10,
    ]);

    // Bots (should be ignored for groups)
    BotView::query()->create([
        'viewable_type' => Post::class,
        'viewable_id' => $post->id,
        'user_agent_id' => $ua->id,
        'last_seen_at' => now(),
        'hits' => 3,
    ]);

    $response = $this->actingAs($owner)->get("/_/{$group->slug}/{$post->slug}");

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('app/group/Post')
        ->where('viewStats.consented', 1)
        ->where('viewStats.anonymous', 0)
        ->where('viewStats.bots', 0),
    );
});

it('does not expose view stats on group landing to group members (only owner/admin)', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::factory()->for($owner)->create();
    $group->members()->attach($member);

    $response = $this->actingAs($member)->get("/_/{$group->slug}");

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('app/group/Landing')
        ->where('viewStats', null),
    );
});
