<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GroupService
{
    private const array POST_FIELDS = [
        'id',
        'blog_id',
        'group_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'is_published',
        'visibility',
        'published_at',
        'created_at',
    ];

    public function getUserGroups(User $user): Collection
    {
        return Group::query()
            ->where('user_id', $user->id)
            ->with([
                'posts' => function ($query) {
                    $query->orderByDesc('created_at')
                        ->with([
                            'extensions' => function ($eq) {
                                $eq->oldest();
                            }
                        ])
                        ->select(self::POST_FIELDS);
                }
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    public function createGroup(array $groupData): Group
    {
        return Group::create($groupData);
    }

    public function updateGroup(Group $group, array $groupData): Group
    {
        $group->update($groupData);
        return $group;
    }
}
