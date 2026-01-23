<?php

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('displays regular posts but NOT extension posts on group landing page', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $user->id, 'slug' => 'test-group']);

    // 1. Post marked as 'restricted' (should be visible)
    Post::factory()->create([
        'group_id' => $group->id,
        'visibility' => Post::VIS_RESTRICTED,
        'is_published' => true,
        'published_at' => now()->subDay(),
        'title' => 'Restricted Post',
    ]);

    // 2. Post marked as 'extension' assigned to the group (should NOT be visible)
    Post::factory()->create([
        'group_id' => $group->id,
        'visibility' => Post::VIS_EXTENSION,
        'is_published' => true,
        'published_at' => now()->subDay(),
        'title' => 'Extension Post',
    ]);

    // 3. Post marked as 'public' assigned to the group (should be visible)
    Post::factory()->create([
        'group_id' => $group->id,
        'visibility' => Post::VIS_PUBLIC,
        'is_published' => true,
        'published_at' => now()->subDay(),
        'title' => 'Public Post',
    ]);

    $this->actingAs($user)
        ->get(route('group.landing', $group->slug))
        ->assertInertia(fn($page) => $page
            ->component('app/group/Landing')
            ->has('posts', 2), // We expect 2 posts (Restricted and Public), Extension should be hidden
        );
});

it('does not display unpublished posts on group landing page', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $user->id, 'slug' => 'test-group']);

    Post::factory()->create([
        'group_id' => $group->id,
        'visibility' => Post::VIS_RESTRICTED,
        'is_published' => false,
        'published_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('group.landing', $group->slug))
        ->assertInertia(fn($page) => $page
            ->component('app/group/Landing')
            ->has('posts', 0),
        );
});
