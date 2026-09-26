<?php

namespace App\Policies;

use App\Models\PrivateConversation;
use App\Models\Post;
use App\Models\User;

class PrivateConversationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PrivateConversation $privateConversation): bool
    {
        return $privateConversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Post $post): bool
    {
        $post->loadMissing(['blog', 'group']);

        if ($post->user_id === $user->id || !$post->is_published) {
            return false;
        }

        if ($post->group !== null) {
            return $post->group->is_published && $post->group->user_id !== $user->id;
        }

        return $post->blog !== null
            && $post->blog->is_published
            && $post->blog->user_id !== $user->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PrivateConversation $privateConversation): bool
    {
        return $this->view($user, $privateConversation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PrivateConversation $privateConversation): bool
    {
        return $this->view($user, $privateConversation);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PrivateConversation $privateConversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PrivateConversation $privateConversation): bool
    {
        return false;
    }
}
