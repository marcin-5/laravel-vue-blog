<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Admin-only listing/management access.
     */
    public function viewAny(?User $user): bool
    {
        return (bool) $user?->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
