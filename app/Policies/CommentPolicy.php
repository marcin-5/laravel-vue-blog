<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Thread;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(?User $user, Thread $thread): bool
    {
        return (new ThreadPolicy)->view($user, $thread);
    }

    public function view(?User $user, Comment $comment): bool
    {
        $comment->loadMissing('thread');

        return $this->viewAny($user, $comment->thread);
    }

    public function create(?User $user, Thread $thread): bool
    {
        return $user !== null
            && !$thread->is_locked
            && $this->viewAny($user, $thread)
            && $thread->post->allow_comments;
    }

    public function update(User $user, Comment $comment): bool
    {
        $comment->loadMissing(['thread.post']);

        return $comment->user_id === $user->id
            && $this->view($user, $comment);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->update($user, $comment);
    }
}
