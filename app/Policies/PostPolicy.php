<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can view any posts.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the post.
     */
    public function view(User $user, Post $post): bool
    {
        // Users can view their own posts or published public posts
        if ($post->blog?->user_id === $user->id) {
            return true;
        }

        // If in a group, check if the user is a member or the owner of the group
        if ($post->group) {
            if ($post->group->user_id === $user->id) {
                return true;
            }

            if ($post->group->members()->where('users.id', $user->id)->exists()) {
                return true;
            }
        }

        // For other users' posts, check if published and (public or unlisted)
        return $post->is_published && in_array($post->visibility, [Post::VIS_PUBLIC, Post::VIS_UNLISTED]);
    }

    /**
     * Determine whether the user can create posts.
     */
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    /**
     * Determine whether the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        if ($post->blog?->user_id === $user->id) {
            return true;
        }

        if ($post->group?->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        if ($post->blog?->user_id === $user->id) {
            return true;
        }

        if ($post->group?->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the post.
     */
    public function restore(User $user, Post $post): bool
    {
        if ($post->blog?->user_id === $user->id) {
            return true;
        }

        if ($post->group?->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the post.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        if ($post->blog?->user_id === $user->id) {
            return true;
        }

        if ($post->group?->user_id === $user->id) {
            return true;
        }

        return false;
    }
}
