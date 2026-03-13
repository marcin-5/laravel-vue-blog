<?php

namespace App\Queries\Blogger;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GroupMembersQuery
{
    private const int MAX_ITEMS_PER_PAGE = 100000;

    /**
     * Handle the query to fetch group members for a blog owner.
     */
    public function handle(Request $request, int $ownerId): LengthAwarePaginator
    {
        $query = User::query()
            ->select(['users.id', 'users.name', 'users.email'])
            ->addSelect(['gu.group_id', 'gu.role', 'gu.joined_at'])
            ->join('group_user as gu', 'gu.user_id', '=', 'users.id')
            ->join('groups as g', 'g.id', '=', 'gu.group_id')
            ->where('g.user_id', $ownerId);

        return $query
            ->when($request->integer('group_id'), fn(Builder $q, int $id) => $q->where('g.id', $id))
            ->when(
                $this->isValidSort($request->get('sort_by')),
                fn(Builder $q) => $q->orderBy($this->getSortColumn($request), $this->getSortDirection($request)),
                fn(Builder $q) => $q->orderBy('users.email', 'asc'),
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
     * Get the fully qualified sort column.
     */
    private function getSortColumn(Request $request): string
    {
        $sortBy = $request->get('sort_by', 'email');

        return match ($sortBy) {
            'name', 'email' => "users.{$sortBy}",
            'joined_at', 'role' => "gu.{$sortBy}",
            default => 'users.email',
        };
    }

    /**
     * Get the sort direction.
     */
    private function getSortDirection(Request $request): string
    {
        return $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';
    }

    /**
     * Get the number of items per page.
     */
    private function getPerPage(Request $request): int
    {
        $perPage = $request->get('per_page', 10);

        if ($perPage === 'all') {
            return self::MAX_ITEMS_PER_PAGE;
        }

        return max(1, (int) $perPage);
    }
}
