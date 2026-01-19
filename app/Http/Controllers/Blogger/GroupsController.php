<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupsController extends AuthenticatedController
{
    public function __construct(
        private readonly GroupService $groupService,
    ) {
        parent::__construct();
        $this->authorizeResource(Group::class, 'group');
    }

    /**
     * Display a listing of the authenticated user's groups.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Group::class);
        $user = $request->user();

        $groups = $this->groupService->getUserGroups($user);

        return Inertia::render('app/blogger/Groups', [
            'groups' => $groups,
            'canCreate' => $user->isBlogger() || $user->isAdmin(), // Or a more specific check if needed
        ]);
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $groupData = $request->getGroupData();
        $this->groupService->createGroup($groupData);

        return redirect()->route('groups.index')->with('success', __('groups.messages.group_created'));
    }

    /**
     * Update the specified group in storage.
     */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $groupData = $request->getGroupData();
        $this->groupService->updateGroup($group, $groupData);

        return back()->with('success', __('groups.messages.group_updated'));
    }
}
