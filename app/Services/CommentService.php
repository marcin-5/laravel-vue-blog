<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommentService
{
    /**
     * @param  array{title: string, visibility: string, content: string}  $data
     */
    public function createThread(Post $post, User $user, array $data): Thread
    {
        return DB::transaction(function () use ($post, $user, $data): Thread {
            $thread = $post->threads()->create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'visibility' => $data['visibility'],
            ]);

            $thread->comments()->create([
                'user_id' => $user->id,
                'content' => $data['content'],
                'depth' => 1,
            ]);

            return $thread->load('user')->loadCount('comments');
        });
    }

    /**
     * @param  array{parent_id?: int|null, content: string}  $data
     */
    public function createComment(Thread $thread, User $user, array $data): Comment
    {
        return DB::transaction(function () use ($thread, $user, $data): Comment {
            $lockedThread = Thread::query()
                ->with('post')
                ->lockForUpdate()
                ->findOrFail($thread->id);

            if ($lockedThread->is_locked || !$lockedThread->post->allow_comments) {
                throw new AuthorizationException;
            }

            $parent = null;
            if (($data['parent_id'] ?? null) !== null) {
                $parent = Comment::query()
                    ->where('thread_id', $lockedThread->id)
                    ->lockForUpdate()
                    ->findOrFail($data['parent_id']);
            }

            $depth = ($parent?->depth ?? 0) + 1;
            $maximumDepth = $lockedThread->post->comments_max_depth;
            if ($maximumDepth > 0 && $depth > $maximumDepth) {
                throw ValidationException::withMessages([
                    'parent_id' => 'The maximum comment depth has been reached.',
                ]);
            }

            return $lockedThread->comments()->create([
                'user_id' => $user->id,
                'parent_id' => $parent?->id,
                'content' => $data['content'],
                'depth' => $depth,
            ])->load('user');
        });
    }
}
