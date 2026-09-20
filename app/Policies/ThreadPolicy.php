<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;

class ThreadPolicy
{
    public function viewAny(?User $user, Post $post): bool
    {
        $post->loadMissing(['blog', 'group']);

        if ($post->group !== null) {
            return $user !== null && $this->isGroupMember($user, $post);
        }

        if ($post->blog === null || !$post->blog->is_published || !$post->is_published) {
            return false;
        }

        return $post->visibility !== Post::VIS_REGISTERED || $user !== null;
    }

    public function view(?User $user, Thread $thread): bool
    {
        $thread->loadMissing('post');

        if (!$this->viewAny($user, $thread->post)) {
            return false;
        }

        return $thread->visibility === Thread::VIS_PUBLIC || $user !== null;
    }

    public function create(?User $user, Post $post): bool
    {
        return $user !== null
            && $post->allow_comments
            && $this->viewAny($user, $post);
    }

    private function isGroupMember(User $user, Post $post): bool
    {
        return $post->group->user_id === $user->id
            || $post->group->members()->where('users.id', $user->id)->exists();
    }
}
