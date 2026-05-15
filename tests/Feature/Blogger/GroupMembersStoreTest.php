<?php

namespace Tests\Feature\Blogger;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JsonException;
use Tests\TestCase;

class GroupMembersStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws JsonException
     */
    public function test_owner_can_add_member(): void
    {
        $owner = User::factory()->create(['role' => 'blogger']);
        $group = Group::factory()->create(['user_id' => $owner->id]);
        $userToAdd = User::factory()->create();

        $response = $this->actingAs($owner)->post(route('blogger.groups.members.store', $group), [
            'email' => $userToAdd->email,
            'role' => GroupMember::ROLE_MEMBER,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $userToAdd->id,
            'role' => GroupMember::ROLE_MEMBER,
        ]);
    }

    public function test_non_owner_cannot_add_member(): void
    {
        $owner = User::factory()->create(['role' => 'blogger']);
        $otherUser = User::factory()->create(['role' => 'blogger']);
        $group = Group::factory()->create(['user_id' => $owner->id]);
        $userToAdd = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(route('blogger.groups.members.store', $group), [
            'email' => $userToAdd->email,
            'role' => GroupMember::ROLE_MEMBER,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('group_user', [
            'group_id' => $group->id,
            'user_id' => $userToAdd->id,
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_admin_can_add_member_to_any_group(): void
    {
        $owner = User::factory()->create(['role' => 'blogger']);
        $admin = User::factory()->create(['role' => 'admin']);
        $group = Group::factory()->create(['user_id' => $owner->id]);
        $userToAdd = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('blogger.groups.members.store', $group), [
            'email' => $userToAdd->email,
            'role' => GroupMember::ROLE_MEMBER,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $userToAdd->id,
        ]);
    }

    public function test_owner_can_update_member_role(): void
    {
        $owner = User::factory()->create(['role' => 'blogger']);
        $group = Group::factory()->create(['user_id' => $owner->id]);
        $member = User::factory()->create();
        $group->members()->attach($member->id, ['role' => GroupMember::ROLE_MEMBER]);

        $response = $this->actingAs($owner)->patch(route('blogger.groups.members.update', [$group, $member]), [
            'role' => GroupMember::ROLE_MODERATOR,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupMember::ROLE_MODERATOR,
        ]);
    }

    public function test_owner_can_remove_member(): void
    {
        $owner = User::factory()->create(['role' => 'blogger']);
        $group = Group::factory()->create(['user_id' => $owner->id]);
        $member = User::factory()->create();
        $group->members()->attach($member->id, ['role' => GroupMember::ROLE_MEMBER]);

        $response = $this->actingAs($owner)->delete(route('blogger.groups.members.destroy', [$group, $member]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('group_user', [
            'group_id' => $group->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_unauthorized_user_cannot_update_or_remove_member(): void
    {
        $owner = User::factory()->create(['role' => 'blogger']);
        $otherUser = User::factory()->create(['role' => 'blogger']);
        $group = Group::factory()->create(['user_id' => $owner->id]);
        $member = User::factory()->create();
        $group->members()->attach($member->id, ['role' => GroupMember::ROLE_MEMBER]);

        $responseUpdate = $this->actingAs($otherUser)->patch(route('blogger.groups.members.update', [$group, $member]),
            [
                'role' => GroupMember::ROLE_MODERATOR,
            ]);
        $responseUpdate->assertForbidden();

        $responseDelete = $this->actingAs($otherUser)->delete(route('blogger.groups.members.destroy', [$group, $member]),
        );
        $responseDelete->assertForbidden();
    }
}
