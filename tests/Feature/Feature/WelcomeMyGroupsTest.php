<?php

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('welcome page passes user groups to logged-in users with groups', function () {
    $user = User::factory()->create();
    $group1 = Group::factory()->create(['user_id' => $user->id]);
    $group2 = Group::factory()->create(['user_id' => $user->id]);
    $user->groups()->attach(
        [$group1->id, $group2->id],
        ['role' => GroupMember::ROLE_MEMBER],
    );

    $response = $this->actingAs($user)->get('/');

    $response->assertInertia(
        fn(Assert $page) => $page
            ->component('public/Welcome')
            ->has('userGroups', 2)
            ->where('userGroups.0.id', $group1->id)
            ->where('userGroups.0.name', $group1->name)
            ->where('userGroups.0.slug', $group1->slug)
            ->where('userGroups.1.name', $group2->name)
            ->where('userGroups.1.slug', $group2->slug)
            ->where('userGroups.1.id', $group2->id),
    );
});

test('welcome page passes empty user groups array to logged-in users without groups', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertInertia(
        fn(Assert $page) => $page
            ->component('public/Welcome')
            ->has('userGroups', 0),
    );
});

test('welcome page does not pass user groups to guests', function () {
    $response = $this->get('/');

    $response->assertInertia(
        fn(Assert $page) => $page
            ->component('public/Welcome')
            ->has('userGroups', 0),
    );
});
