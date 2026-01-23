<?php

namespace Tests\Feature\Queries\Blogger;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Queries\Blogger\GroupMembersQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->otherUser = User::factory()->create();

    $this->group1 = Group::factory()->create(['user_id' => $this->owner->id, 'name' => 'Group A']);
    $this->group2 = Group::factory()->create(['user_id' => $this->owner->id, 'name' => 'Group B']);
    $this->otherGroup = Group::factory()->create(['user_id' => $this->otherUser->id, 'name' => 'Other Group']);

    $this->user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $this->user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    $this->user3 = User::factory()->create(['name' => 'Alice Brown', 'email' => 'alice@example.com']);

    // Add users to group1
    $this->group1->members()->attach(
        $this->user1->id,
        ['role' => GroupMember::ROLE_MEMBER, 'joined_at' => now()->subDays(2)],
    );
    $this->group1->members()->attach(
        $this->user2->id,
        ['role' => GroupMember::ROLE_MODERATOR, 'joined_at' => now()->subDays(1)],
    );

    // Add user3 to group2
    $this->group2->members()->attach($this->user3->id, ['role' => GroupMember::ROLE_MEMBER, 'joined_at' => now()]);

    // Add someone to otherGroup
    $this->otherGroup->members()->attach($this->user1->id, ['role' => GroupMember::ROLE_MEMBER, 'joined_at' => now()]);

    $this->query = new GroupMembersQuery();
});

it('returns members of all groups owned by the owner', function () {
    $request = new Request();
    $result = $this->query->handle($request, $this->owner->id);

    expect($result->total())->toBe(3);
    $emails = $result->pluck('email')->toArray();
    expect($emails)->toContain('john@example.com', 'jane@example.com', 'alice@example.com');
    expect($emails)->not->toContain($this->otherUser->email);
});

it('filters members by group_id', function () {
    $request = new Request(['group_id' => $this->group1->id]);
    $result = $this->query->handle($request, $this->owner->id);

    expect($result->total())->toBe(2);
    $emails = $result->pluck('email')->toArray();
    expect($emails)->toContain('john@example.com', 'jane@example.com');
    expect($emails)->not->toContain('alice@example.com');
});

it('does not return members of groups not owned by the owner', function () {
    $request = new Request(['group_id' => $this->otherGroup->id]);
    // The query has ->where('g.user_id', $ownerId) and joins groups as g
    // So if we filter by a group that doesn't belong to the owner, it should return empty
    $result = $this->query->handle($request, $this->owner->id);

    expect($result->total())->toBe(0);
});

it('handles per_page parameter', function () {
    $request = new Request(['per_page' => 1]);
    $result = $this->query->handle($request, $this->owner->id);

    expect($result->perPage())->toBe(1);
    expect($result->count())->toBe(1);
    expect($result->total())->toBe(3);
});

it('handles per_page=all parameter', function () {
    $request = new Request(['per_page' => 'all']);
    $result = $this->query->handle($request, $this->owner->id);

    expect($result->perPage())->toBe(100000);
    expect($result->total())->toBe(3);
});

it('sorts members by email ascending by default', function () {
    $request = new Request();
    $result = $this->query->handle($request, $this->owner->id);

    $emails = $result->pluck('email')->toArray();
    expect($emails[0])->toBe('alice@example.com');
    expect($emails[1])->toBe('jane@example.com');
    expect($emails[2])->toBe('john@example.com');
});

it('sorts members by name descending', function () {
    $request = new Request(['sort_by' => 'name', 'sort_dir' => 'desc']);
    $result = $this->query->handle($request, $this->owner->id);

    $names = $result->pluck('name')->toArray();
    expect($names[0])->toBe('John Doe');
    expect($names[1])->toBe('Jane Smith');
    expect($names[2])->toBe('Alice Brown');
});

it('sorts members by role', function () {
    $request = new Request(['sort_by' => 'role', 'sort_dir' => 'asc']);
    $result = $this->query->handle($request, $this->owner->id);

    $roles = $result->pluck('role')->toArray();
    // member, member, moderator (alphabetical)
    expect($roles)->toBe([
        GroupMember::ROLE_MEMBER,
        GroupMember::ROLE_MEMBER,
        GroupMember::ROLE_MODERATOR
    ]);
});

it('sorts members by joined_at', function () {
    $request = new Request(['sort_by' => 'joined_at', 'sort_dir' => 'desc']);
    $result = $this->query->handle($request, $this->owner->id);

    $emails = $result->pluck('email')->toArray();
    // user3 (now), user2 (now-1), user1 (now-2)
    expect($emails[0])->toBe('alice@example.com');
    expect($emails[1])->toBe('jane@example.com');
    expect($emails[2])->toBe('john@example.com');
});
