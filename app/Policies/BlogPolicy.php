<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;

class BlogPolicy
{
    /**
     * Determine whether the user can view any blogs (dashboard list).
     */
    public function viewAny(?User $user): bool
    {
        return (bool) $user; // authenticated users only
    }

    /**
     * Determine whether the user can create a blog.
     * Admins can always create; bloggers must be under their quota.
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->canCreateBlog();
    }

    /**
     * Determine whether the user can update the blog.
     * Owner or Admin.
     */
    public function update(User $user, Blog $blog): bool
    {
        return $user->isAdmin() || $blog->user_id === $user->id;
    }
}
