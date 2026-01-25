<?php

namespace App\Queries\App;

use App\Models\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GroupPostsQuery
{
    public function handle(Group $group): LengthAwarePaginator
    {
        return $group->posts()
            ->forGroupView()
            ->paginate($group->page_size ?? 15);
    }
}
