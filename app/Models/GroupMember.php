<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupMember extends Pivot
{
    public const ROLE_MEMBER = 'member';

    public const ROLE_MODERATOR = 'moderator';

    protected $table = 'group_user';

    protected $casts = [
        'joined_at' => 'datetime',
    ];
}
