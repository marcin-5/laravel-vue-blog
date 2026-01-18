<?php

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows owner to access group landing', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $user->id, 'slug' => 'test-group']);

    $this->actingAs($user)
        ->get(route('group.landing', $group->slug))
        ->assertOk();
});

it('allows member to access group landing', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $owner->id, 'slug' => 'test-group']);
    $group->members()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($member)
        ->get(route('group.landing', $group->slug))
        ->assertOk();
});

it('denies non-member access to group landing', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $owner->id, 'slug' => 'test-group']);

    $this->actingAs($nonMember)
        ->get(route('group.landing', $group->slug))
        ->assertForbidden();
});

it('displays restricted posts only in groups', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $user->id, 'slug' => 'test-group']);

    $post = Post::factory()->create([
        'group_id' => $group->id,
        'visibility' => Post::VIS_RESTRICTED,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('group.landing', $group->slug))
        ->assertInertia(fn($page) => $page
            ->component('app/group/Landing')
            ->has('posts', 1),
        );
});
