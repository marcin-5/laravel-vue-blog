<?php

namespace App\Services\Blogger;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;

class GroupMemberService
{
    /**
     * Add a member to a group.
     */
    public function addMember(Group $group, string $email, string $role = GroupMember::ROLE_MEMBER): void
    {
        $userToAdd = User::where('email', $email)->firstOrFail();

        $group->members()->syncWithoutDetaching([
            $userToAdd->id => [
                'role' => $role,
                'joined_at' => now(),
            ],
        ]);
    }

    /**
     * Update a member's role in a group.
     */
    public function updateMember(Group $group, User $user, string $role): void
    {
        $group->members()->updateExistingPivot($user->id, [
            'role' => $role,
        ]);
    }

    /**
     * Remove a member from a group.
     */
    public function removeMember(Group $group, User $user): void
    {
        $group->members()->detach($user->id);
    }
}
