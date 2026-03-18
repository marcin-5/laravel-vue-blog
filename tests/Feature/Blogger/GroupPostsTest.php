<?php

use App\Models\Group;
use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('can create a post for a group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->post(route('posts.store'), [
            'group_id' => $group->id,
            'title' => 'Post in Group',
            'content' => 'Content of the group post',
        ])
        ->assertRedirect();

    $post = Post::where('title', 'Post in Group')->first();
    expect($post)->not
        ->toBeNull()
        ->and($post->group_id)->toBe($group->id)
        ->and($post->blog_id)->toBeNull();
});

it('cannot create a post for a group not owned by the user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)
        ->post(route('posts.store'), [
            'group_id' => $group->id,
            'title' => 'Unauthorized Post',
            'content' => 'Content',
        ])
        ->assertForbidden();
});

it('contributor can create a post in a group', function () {
    $owner = User::factory()->create();
    $contributor = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $owner->id]);
    $group->members()->attach($contributor->id, ['role' => 'contributor']);

    actingAs($contributor)
        ->post(route('posts.store'), [
            'group_id' => $group->id,
            'title' => 'Contributor Post',
            'content' => 'Content by contributor',
        ])
        ->assertRedirect();

    $post = Post::where('title', 'Contributor Post')->first();
    expect($post)->not
        ->toBeNull()
        ->and($post->group_id)->toBe($group->id);
});

it('contributor can update a post in a group', function () {
    $owner = User::factory()->create();
    $contributor = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $owner->id]);
    $group->members()->attach($contributor->id, ['role' => 'contributor']);
    $post = Post::factory()->create(['group_id' => $group->id, 'user_id' => $owner->id]);

    actingAs($contributor)
        ->patch(route('posts.update', $post), [
            'title' => 'Updated by Contributor',
            'content' => 'Updated content',
        ])
        ->assertRedirect();

    expect($post->fresh()->title)->toBe('Updated by Contributor');
});

it('regular member cannot create a post in a group', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $owner->id]);
    $group->members()->attach($member->id, ['role' => 'member']);

    actingAs($member)
        ->post(route('posts.store'), [
            'group_id' => $group->id,
            'title' => 'Unauthorized Post',
            'content' => 'Content',
        ])
        ->assertForbidden();
});
