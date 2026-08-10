<?php

namespace Tests\Feature\Blogger;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GroupMembersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('blogger.groups.members.index'))
            ->assertRedirect(route('login'));
    }

    public function test_blogger_receives_only_their_groups_and_members(): void
    {
        $owner = User::factory()->create(['role' => 'blogger']);
        $otherOwner = User::factory()->create(['role' => 'blogger']);
        $group = Group::factory()->create(['user_id' => $owner->id]);
        $otherGroup = Group::factory()->create(['user_id' => $otherOwner->id]);
        $member = User::factory()->create(['email' => 'member@example.com']);
        $otherMember = User::factory()->create(['email' => 'other@example.com']);

        $group->members()->attach($member->id, ['role' => GroupMember::ROLE_MEMBER]);
        $otherGroup->members()->attach($otherMember->id, ['role' => GroupMember::ROLE_MEMBER]);

        $this->actingAs($owner)
            ->get(route('blogger.groups.members.index', ['owner_id' => $otherOwner->id]))
            ->assertSuccessful()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/blogger/GroupMembers')
                ->where('isAdmin', false)
                ->where('filters.owner_id', $owner->id)
                ->has('groups', 1)
                ->where('groups.0.id', $group->id)
                ->has('members', 1)
                ->where('members.0.email', $member->email)
                ->where('members.0.group_id', $group->id)
                ->has('owners', 0),
            );
    }

    public function test_admin_can_filter_by_owner_group_sorting_and_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'blogger', 'name' => 'Owner A']);
        $otherOwner = User::factory()->create(['role' => 'blogger', 'name' => 'Owner B']);
        $group = Group::factory()->create(['user_id' => $otherOwner->id]);
        $ignoredGroup = Group::factory()->create(['user_id' => $owner->id]);
        $alpha = User::factory()->create(['name' => 'Alpha Member']);
        $beta = User::factory()->create(['name' => 'Beta Member']);
        $ignoredMember = User::factory()->create(['name' => 'Ignored Member']);

        $group->members()->attach($alpha->id, ['role' => GroupMember::ROLE_MEMBER]);
        $group->members()->attach($beta->id, ['role' => GroupMember::ROLE_MODERATOR]);
        $ignoredGroup->members()->attach($ignoredMember->id, ['role' => GroupMember::ROLE_MEMBER]);

        $this->actingAs($admin)
            ->get(route('blogger.groups.members.index', [
                'owner_id' => $otherOwner->id,
                'group_id' => $group->id,
                'per_page' => 1,
                'sort_by' => 'name',
                'sort_dir' => 'desc',
            ]))
            ->assertSuccessful()
            ->assertInertia(fn(Assert $page) => $page
                ->component('app/blogger/GroupMembers')
                ->where('isAdmin', true)
                ->where('filters.owner_id', $otherOwner->id)
                ->where('filters.group_id', $group->id)
                ->where('filters.per_page', '1')
                ->where('filters.sort_by', 'name')
                ->where('filters.sort_dir', 'desc')
                ->has('owners', 2)
                ->where('owners.0.id', $owner->id)
                ->where('owners.1.id', $otherOwner->id)
                ->has('groups', 1)
                ->where('groups.0.id', $group->id)
                ->has('members', 1)
                ->where('members.0.name', $beta->name)
                ->where('pagination.total', 2)
                ->where('pagination.last_page', 2),
            );
    }
}
