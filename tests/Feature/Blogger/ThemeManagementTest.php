<?php

use App\Models\Blog;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lists only blogs the current user can update for theme management', function () {
    $user = User::factory()->create(['role' => 'blogger']);
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'Owned blog',
        'theme' => ['light' => ['--background' => '#ffffff']],
    ]);
    Blog::factory()->create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Other blog',
    ]);

    $this->actingAs($user)
        ->get(route('blogger.blogs.theme.index'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/blogger/ThemeManagement')
            ->where('entityType', 'blog')
            ->has('entities', 1)
            ->where('entities.0.id', $blog->id)
            ->where('entities.0.name', 'Owned blog')
            ->where('entities.0.theme.light.--background', '#ffffff'),
        );
});

it('lists only owned or maintainable groups for theme management', function () {
    $user = User::factory()->create(['role' => 'blogger']);
    $ownedGroup = Group::factory()->create(['user_id' => $user->id, 'name' => 'Owned group']);
    $maintainableGroup = Group::factory()->create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Maintainable group',
    ]);
    $memberOnlyGroup = Group::factory()->create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Member only group',
    ]);
    Group::factory()->create([
        'user_id' => User::factory()->create()->id,
        'name' => 'Other group',
    ]);

    $maintainableGroup->members()->attach($user->id, ['role' => GroupMember::ROLE_MAINTAINER]);
    $memberOnlyGroup->members()->attach($user->id, ['role' => GroupMember::ROLE_MEMBER]);

    $this->actingAs($user)
        ->get(route('blogger.groups.theme.index'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/blogger/ThemeManagement')
            ->where('entityType', 'group')
            ->has('entities', 2)
            ->where('entities.0.id', $maintainableGroup->id)
            ->where('entities.1.id', $ownedGroup->id),
        );
});

it('updates only the selected blog theme', function () {
    $user = User::factory()->create(['role' => 'blogger']);
    $blog = Blog::factory()->create([
        'user_id' => $user->id,
        'name' => 'Unchanged blog name',
        'theme' => ['light' => ['--background' => '#ffffff']],
    ]);
    $theme = ['light' => ['--background' => '#eeeeee'], 'dark' => ['--background' => '#111111']];

    $this->actingAs($user)
        ->patch(route('blogs.update', $blog), ['theme' => $theme])
        ->assertRedirect();

    expect($blog->refresh()->theme)->toBe($theme)
        ->and($blog->name)->toBe('Unchanged blog name');
});

it('updates only the selected group theme', function () {
    $user = User::factory()->create(['role' => 'blogger']);
    $group = Group::factory()->create([
        'user_id' => $user->id,
        'name' => 'Unchanged group name',
        'theme' => ['light' => ['--background' => '#ffffff']],
    ]);
    $theme = ['light' => ['--background' => '#eeeeee'], 'dark' => ['--background' => '#111111']];

    $this->actingAs($user)
        ->patch(route('blogger.groups.content.update', $group), ['theme' => $theme])
        ->assertRedirect();

    expect($group->refresh()->theme)->toBe($theme)
        ->and($group->name)->toBe('Unchanged group name');
});
