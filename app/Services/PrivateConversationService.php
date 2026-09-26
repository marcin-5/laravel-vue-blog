<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PrivateConversation;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PrivateConversationService
{
    /**
     * @param  array{subject: string, content: string, email_notifications?: bool}  $data
     */
    public function create(Post $post, User $initiator, array $data): PrivateConversation
    {
        return DB::transaction(function () use ($post, $initiator, $data): PrivateConversation {
            $post->loadMissing(['blog', 'group']);

            $ownerId = $post->group?->user_id ?? $post->blog?->user_id;

            if ($ownerId === null || $ownerId === $initiator->id) {
                throw new AuthorizationException;
            }

            $conversation = PrivateConversation::create([
                'blog_id' => $post->blog_id,
                'group_id' => $post->group_id,
                'post_id' => $post->id,
                'initiator_id' => $initiator->id,
                'owner_id' => $ownerId,
                'subject' => $data['subject'],
            ]);

            $conversation->messages()->create([
                'user_id' => $initiator->id,
                'content' => $data['content'],
            ]);

            $conversation->participants()->createMany([
                [
                    'user_id' => $initiator->id,
                    'email_notifications' => $data['email_notifications'] ?? true,
                ],
                [
                    'user_id' => $ownerId,
                    'email_notifications' => true,
                ],
            ]);

            return $conversation->load(['messages.user', 'participants']);
        });
    }

    public function reply(PrivateConversation $conversation, User $user, string $content): PrivateMessage
    {
        return DB::transaction(function () use ($conversation, $user, $content): PrivateMessage {
            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'content' => $content,
            ]);

            $conversation->touch();

            return $message->load('user');
        });
    }

    public function updateMessage(PrivateMessage $message, string $content): PrivateMessage
    {
        return DB::transaction(function () use ($message, $content): PrivateMessage {
            $message->update(['content' => $content]);
            $message->conversation()->touch();

            return $message->load('user');
        });
    }

    public function deleteMessage(PrivateMessage $message): void
    {
        DB::transaction(function () use ($message): void {
            $conversation = $message->conversation;
            $message->delete();
            $conversation?->touch();
        });
    }
}
