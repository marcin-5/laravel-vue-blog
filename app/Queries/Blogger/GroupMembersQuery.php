<?php

namespace App\Queries\Blogger;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GroupMembersQuery
{
    public function handle(Request $request, int $ownerId): LengthAwarePaginator
    {
        $groupId = $request->integer('group_id') ?: null;
        $perPage = $request->get('per_page', 10);
        $perPage = $perPage === 'all' ? 100000 : (int)$perPage;
        $sortBy = $request->get('sort_by', 'email');
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        return User::query()
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
            )
            ->paginate($perPage)
            ->withQueryString();
    }
}
