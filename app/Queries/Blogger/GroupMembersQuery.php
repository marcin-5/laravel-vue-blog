<?php

declare(strict_types=1);

namespace App\Queries\Blogger;

use App\Builders\UserBuilder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class GroupMembersQuery
{
    private const int MAX_ITEMS_PER_PAGE = 100000;

    /**
     * Handle the query to fetch group members for a blog owner.
     */
    public function handle(Request $request, int $ownerId): LengthAwarePaginator
    {
        return User::query()
            ->select(['users.id', 'users.name', 'users.email'])
            ->withGroupMembership()
            ->forGroupOwner($ownerId)
            ->when($request->integer('group_id'), fn(UserBuilder $q, int $id) => $q->inGroup($id))
            ->when(
                $this->isValidSort($request->input('sort_by')),
                fn(UserBuilder $q) => $q->orderByMemberField(
                    $request->input('sort_by'),
                    $request->input('sort_dir', 'asc'),
                ),
                fn(UserBuilder $q) => $q->orderBy('users.email', 'asc'),
            )
            ->paginate($this->getPerPage($request))
            ->withQueryString();
    }

    /**
     * Determine if the provided sort field is valid.
     */
    private function isValidSort(?string $sortBy): bool
    {
        return in_array($sortBy, ['email', 'name', 'joined_at', 'role'], true);
    }

    /**
     * Get the number of items per page.
     */
    private function getPerPage(Request $request): int
    {
        $perPage = $request->input('per_page', 10);

        if ($perPage === 'all') {
            return self::MAX_ITEMS_PER_PAGE;
        }

        return max(1, (int) $perPage);
    }
}
