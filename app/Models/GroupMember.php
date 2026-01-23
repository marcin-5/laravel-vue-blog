<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupMember extends Pivot
{
    public const ROLE_MEMBER = 'member';

    public const ROLE_MODERATOR = 'moderator';

    // Contributor: can add/edit content in the group
    public const ROLE_CONTRIBUTOR = 'contributor';

    // Maintainer: combines "contributor" and "moderator" permissions
    public const ROLE_MAINTAINER = 'maintainer';

    protected $table = 'group_user';

    protected $casts = [
        'joined_at' => 'datetime',
    ];
}
