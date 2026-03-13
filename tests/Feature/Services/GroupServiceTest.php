<?php

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it(
    'getUserGroups returns users groups with posts ordered by created_at desc without updated_at and with loaded extensions',
    function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Group::factory()->count(1)->create(['user_id' => $otherUser->id]);

        $oldGroup = Group::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(1)]);
        $newGroup = Group::factory()->create(['user_id' => $user->id]);

        // posts for newGroup
        $extensionPost = Post::factory()->create(['group_id' => $newGroup->id, 'created_at' => now()->subHour()]);
        $oldPost = Post::factory()->create(['group_id' => $newGroup->id, 'created_at' => now()->subMinutes(30)]);
        $newPost = Post::factory()->create(['group_id' => $newGroup->id]);

        // create extension for newPost
        DB::table('post_extensions')->insert([
            'post_id' => $newPost->id,
            'extension_post_id' => $extensionPost->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = new GroupService;
        $groups = $service->getUserGroups($user);

        expect($groups)->toHaveCount(2)
            ->and($groups->first())->not->toBeNull()->id->toBe($newGroup->id)
            ->and($groups->last())->not->toBeNull()->id->toBe($oldGroup->id)
            ->and($groups->first())->not->toBeNull()->relationLoaded('posts')->toBeTrue()
            ->and($groups->first())->not->toBeNull()->posts->count()->toBe(3)
            ->and($groups->first())->not->toBeNull()->posts->first()->id->toBe($newPost->id)
            ->and($groups->first())->not->toBeNull()->posts->first()->updated_at->toBeNull()
            ->and($groups->first())->not->toBeNull()->posts->first()->relationLoaded('extensions')->toBeTrue()
            ->and($groups->first())->not->toBeNull()->posts->first()->extensions->count()->toBe(1);
    },
);

it('createGroup creates a new group with provided data', function () {
    $user = User::factory()->create();
    $data = [
        'user_id' => $user->id,
        'name' => 'Test Group',
        'slug' => 'test-group',
        'content' => 'Test content',
    ];
    $service = new GroupService;
    $group = $service->createGroup($data);
    $freshGroup = $group->fresh();

    expect($freshGroup)->toBeInstanceOf(Group::class)
        ->name->toBe('Test Group')
        ->slug->toBe('test-group')
        ->content->toBe('Test content')
        ->user_id->toBe($user->id);
});

it('updateGroup updates group with new data', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create(['user_id' => $user->id]);
    $data = [
        'name' => 'Updated Group',
        'slug' => 'updated-group',
    ];
    $service = new GroupService;
    $service->updateGroup($group, $data);
    $freshGroup = $group->fresh();

    expect($freshGroup)->name->toBe('Updated Group')
        ->slug->toBe('updated-group');
});
