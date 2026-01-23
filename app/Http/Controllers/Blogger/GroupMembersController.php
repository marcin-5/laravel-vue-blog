<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupMembersController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        // Robust role detection: isAdmin() method, is_admin field, or role === 'admin'
        $isAdmin = (method_exists($user, 'isAdmin') && $user->isAdmin())
            || ($user->is_admin ?? false)
            || (($user->role ?? null) === 'admin');

        $ownerId = $isAdmin && $request->filled('owner_id')
            ? $request->integer('owner_id')
            : (int)$user->id;

        $groupsQuery = Group::query()->where('user_id', $ownerId);
        $groups = $groupsQuery->select(['id', 'name'])->orderBy('name')->get();

        $groupId = $request->integer('group_id') ?: null;
        $perPage = $request->get('per_page', 10);
        $perPage = $perPage === 'all' ? 100000 : (int)$perPage;
        $sortBy = $request->get('sort_by', 'email');
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $membersQuery = User::query()
            ->select(['users.id', 'users.name', 'users.email'])
            ->join('group_user as gu', 'gu.user_id', '=', 'users.id')
            ->join('groups as g', 'g.id', '=', 'gu.group_id')
            ->when($groupId, fn(Builder $q) => $q->where('g.id', $groupId))
            ->where('g.user_id', $ownerId)
            ->addSelect(['gu.group_id', 'gu.role', 'gu.joined_at'])
            ->when(
                in_array($sortBy, ['email', 'name', 'joined_at', 'role'], true),
                function (Builder $q) use ($sortBy, $sortDir) {
                    $column = in_array($sortBy, ['email', 'name'], true) ? 'users.' . $sortBy : 'gu.' . $sortBy;
                    $q->orderBy($column, $sortDir);
                },
                fn(Builder $q) => $q->orderBy('users.email', 'asc'),
            );

        $members = $membersQuery->paginate($perPage)->withQueryString();

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
                'group_id' => $groupId,
                'per_page' => $request->get('per_page', 10),
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'owner_id' => $ownerId,
            ],
            'isAdmin' => $isAdmin,
            'groups' => $groups,
            'members' => $members,
            'owners' => $owners,
        ]);
    }

    public function store(Request $request, Group $group): RedirectResponse
    {
        $this->authorizeAction($request, $group);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['nullable', 'string'],
        ]);

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

    private function authorizeAction(Request $request, Group $group): void
    {
        $authUser = $request->user();
        $isAdmin = (method_exists($authUser, 'isAdmin') ? (bool)$authUser->isAdmin() : false)
            || (bool)($authUser->is_admin ?? false)
            || (($authUser->role ?? null) === 'admin');
        abort_unless($isAdmin || $group->user_id === $authUser->id, 403);
    }

    public function update(Request $request, Group $group, User $user): RedirectResponse
    {
        $this->authorizeAction($request, $group);

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
        $this->authorizeAction($request, $group);

        $group->members()->detach($user->id);

        return back()->with('success', __('Member removed'));
    }
}
