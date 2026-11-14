<?php

namespace App\Http\Controllers\Blogger;

use App\Builders\SimpleSeoBuilder;
use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use App\Models\GroupMember;
use App\Services\GroupService;
use App\Services\TranslationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupsController extends AuthenticatedController
{
    public function __construct(
        private readonly GroupService $groupService,
        private readonly TranslationService $translations,
        private readonly SimpleSeoBuilder $seoBuilder,
    ) {
        parent::__construct();
        $this->authorizeResource(Group::class, 'group');
    }

    /**
     * Display a listing of the authenticated user's groups.
     * @throws FileNotFoundException
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Group::class);
        $user = $request->user();

        $groups = $this->groupService->getUserGroups($user);

        return Inertia::render('app/blogger/Groups', [
            'groups' => $groups,
            'canCreate' => $user->isBlogger() || $user->isAdmin(), // Or a more specific check if needed
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('dashboard'),
            ],
            'seo' => $this->seoBuilder->build('My Groups')->toArray(),
        ]);
    }

    public function theme(Request $request): Response
    {
        $this->authorize('viewAny', Group::class);
        $user = $request->user();
        $query = Group::query();

        if (!$user->isAdmin() && !$user->hasAbility('manage-group')) {
            $query->where(function (Builder $query) use ($user): void {
                $query
                    ->where('user_id', $user->id)
                    ->orWhereHas('members', function (Builder $memberQuery) use ($user): void {
                        $memberQuery
                            ->whereKey($user->id)
                            ->where('group_user.role', GroupMember::ROLE_MAINTAINER);
                    });
            });
        }

        $entities = $query
            ->orderBy('name')
            ->get(['id', 'user_id', 'name', 'theme'])
            ->filter(fn(Group $group): bool => $user->can('update', $group))
            ->map(fn(Group $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'theme' => $group->theme,
            ])
            ->values();

        return Inertia::render('app/blogger/ThemeManagement', [
            'entityType' => 'group',
            'entities' => $entities,
        ]);
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $groupData = $request->getGroupData();
        $this->groupService->createGroup($groupData);

        return redirect()->route('blogger.groups.content.index')->with('success', __('groups.messages.group_created'));
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
