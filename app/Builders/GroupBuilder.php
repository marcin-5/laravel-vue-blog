<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Group;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of Group
 * @extends Builder<TModelClass>
 */
class GroupBuilder extends Builder
{
    // Empty for now, but ready for future methods
}
