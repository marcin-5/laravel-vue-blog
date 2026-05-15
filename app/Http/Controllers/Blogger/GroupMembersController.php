<?php

namespace App\Http\Controllers\Blogger;

use App\Builders\SimpleSeoBuilder;
use App\Http\Controllers\Concerns\FormatsPaginator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blogger\StoreGroupMemberRequest;
use App\Http\Requests\Blogger\UpdateGroupMemberRequest;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Queries\Blogger\GroupMembersQuery;
use App\Services\Blogger\GroupMemberService;
use App\Services\TranslationService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupMembersController extends Controller
{
    use AuthorizesRequests, FormatsPaginator;

    public function __construct(
        private readonly GroupMemberService $memberService,
        private readonly TranslationService $translations,
        private readonly SimpleSeoBuilder $seoBuilder,
    ) {}

    /**
     * @throws FileNotFoundException
     */
    public function index(Request $request, GroupMembersQuery $query): Response
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $ownerId = $isAdmin && $request->filled('owner_id')
            ? $request->integer('owner_id')
            : (int) $user->id;

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
                'per_page' => $request->query('per_page', 10),
                'sort_by' => $request->query('sort_by', 'email'),
                'sort_dir' => $request->query('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc',
                'owner_id' => $ownerId,
            ],
            'isAdmin' => $isAdmin,
            'groups' => $groups,
            'members' => $members->items(),
            'pagination' => $this->formatPagination($members),
            'owners' => $owners,
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('dashboard'),
            ],
            'seo' => $this->seoBuilder->build('Group Members')->toArray(),
        ]);
    }

    public function store(StoreGroupMemberRequest $request, Group $group): RedirectResponse
    {
        $this->memberService->addMember(
            $group,
            $request->validated('email'),
            $request->validated('role', GroupMember::ROLE_MEMBER),
        );

        return back()->with('success', __('Member added'));
    }

    public function update(UpdateGroupMemberRequest $request, Group $group, User $user): RedirectResponse
    {
        $this->authorize('update', $group);

        $this->memberService->updateMember($group, $user, $request->validated('role'));

        return back()->with('success', __('Role updated'));
    }

    public function destroy(Group $group, User $user): RedirectResponse
    {
        $this->authorize('update', $group);

        $this->memberService->removeMember($group, $user);

        return back()->with('success', __('Member removed'));
    }
}
