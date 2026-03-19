<?php

namespace App\Models;

use App\Enums\GroupRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupMember extends Pivot
{
    public const ROLE_MEMBER = GroupRole::Member->value;

    public const ROLE_MODERATOR = GroupRole::Moderator->value;

    // Contributor: can add/edit content in the group
    public const ROLE_CONTRIBUTOR = GroupRole::Contributor->value;

    // Maintainer: combines "contributor" and "moderator" permissions
    public const ROLE_MAINTAINER = GroupRole::Maintainer->value;

    protected $table = 'group_user';

    protected $casts = [
        'joined_at' => 'datetime',
    ];
}
