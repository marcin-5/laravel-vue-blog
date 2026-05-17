<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @template TModelClass of User
 * @extends Builder<TModelClass>
 */
class UserBuilder extends Builder
{
    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);
    }

    /**
     * Scope: Include group membership details.
     */
    public function withGroupMembership(): self
    {
        return $this
            ->addSelect(['gu.group_id', 'gu.role', 'gu.joined_at'])
            ->join('group_user as gu', 'gu.user_id', '=', 'users.id')
            ->join('groups as g', 'g.id', '=', 'gu.group_id');
    }

    /**
     * Scope: Filter by group owner.
     */
    public function forGroupOwner(int $ownerId): self
    {
        return $this->where('g.user_id', $ownerId);
    }

    /**
     * Scope: Filter by specific group.
     */
    public function inGroup(int $groupId): self
    {
        return $this->where('g.id', $groupId);
    }

    /**
     * Scope: Order by member field.
     */
    public function orderByMemberField(string $column, string $direction = 'asc'): self
    {
        return $this->orderBy($this->resolveMemberColumn($column), $direction);
    }

    /**
     * Resolve sort column to its fully qualified name.
     */
    private function resolveMemberColumn(string $column): string
    {
        return match ($column) {
            'name', 'email' => "users.$column",
            'joined_at', 'role' => "gu.$column",
            default => 'users.email',
        };
    }
}
