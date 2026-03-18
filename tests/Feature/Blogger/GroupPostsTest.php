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
