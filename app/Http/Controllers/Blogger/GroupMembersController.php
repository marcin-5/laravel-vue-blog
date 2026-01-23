<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blogger\StoreGroupMemberRequest;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Queries\Blogger\GroupMembersQuery;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupMembersController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, GroupMembersQuery $query): Response
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $ownerId = $isAdmin && $request->filled('owner_id')
            ? $request->integer('owner_id')
            : (int)$user->id;

        $groups = Group::query()
            ->where('user_id', $ownerId)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $members = $query->handle($request, $ownerId);

        // List of owners (users with at least one group) – for admin only
        $owners = [];
        if ($isAdmin) {
            $owners = User::query()
                ->select(['users.id', 'users.name', 'users.email'])
                ->whereIn('users.id', function ($q) {
                    $q->select('user_id')->from('groups')->distinct();
                })
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('app/blogger/GroupMembers', [
            'filters' => [
                'group_id' => $request->integer('group_id') ?: null,
                'per_page' => $request->get('per_page', 10),
                'sort_by' => $request->get('sort_by', 'email'),
                'sort_dir' => $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc',
                'owner_id' => $ownerId,
            ],
            'isAdmin' => $isAdmin,
            'groups' => $groups,
            'members' => $members,
            'owners' => $owners,
        ]);
    }

    public function store(StoreGroupMemberRequest $request, Group $group): RedirectResponse
    {
        $validated = $request->validated();

        $userToAdd = User::where('email', $validated['email'])->first();
        if (!$userToAdd) {
            return back()->withErrors(['email' => __('validation.exists', ['attribute' => 'email'])]);
        }

        $role = $validated['role'] ?? GroupMember::ROLE_MEMBER;

        $group->members()->syncWithoutDetaching([
            $userToAdd->id => [
                'role' => $role,
                'joined_at' => now(),
            ],
        ]);

        return back()->with('success', __('Member added'));
    }

    public function update(Request $request, Group $group, User $user): RedirectResponse
    {
        $this->authorize('update', $group);

        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $role = $validated['role'];

        $group->members()->updateExistingPivot($user->id, [
            'role' => $role,
        ]);

        return back()->with('success', __('Role updated'));
    }

    public function destroy(Request $request, Group $group, User $user): RedirectResponse
    {
        $this->authorize('update', $group);

        $group->members()->detach($user->id);

        return back()->with('success', __('Member removed'));
    }
}
