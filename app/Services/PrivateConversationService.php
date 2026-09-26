<?php

namespace App\Services;

use App\Jobs\SendPrivateMessageNotification;
use App\Models\Post;
use App\Models\PrivateConversation;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Throwable;

class PrivateConversationService
{
    /**
     * @throws Throwable
     */
    public function reply(PrivateConversation $conversation, User $user, string $content): PrivateMessage
    {
        return DB::transaction(function () use ($conversation, $user, $content): PrivateMessage {
            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'content' => $content,
                'notification_version' => 1,
            ]);

            $conversation->touch();
            $this->scheduleNotification($message);

            return $message->load('user');
        });
    }

    /**
     * @param  array{subject: string, content: string, email_notifications?: bool}  $data
     * @throws Throwable
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

            $message = $conversation->messages()->create([
                'user_id' => $initiator->id,
                'content' => $data['content'],
                'notification_version' => 1,
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

            $this->scheduleNotification($message);

            return $conversation->load(['messages.user', 'participants']);
        });
    }

    private function scheduleNotification(PrivateMessage $message): void
    {
        SendPrivateMessageNotification::dispatch(
            $message->id,
            $message->notification_version,
        )->delay(now()->addMinutes(15))->afterCommit();
    }

    /**
     * @throws Throwable
     */
    public function updateMessage(PrivateMessage $message, string $content): PrivateMessage
    {
        return DB::transaction(function () use ($message, $content): PrivateMessage {
            $message->update(['content' => $content]);
            $message->increment('notification_version');
            $message->conversation()->touch();

            $message->refresh();
            $this->scheduleNotification($message);

            return $message->load('user');
        });
    }

    /**
     * @throws Throwable
     */
    public function deleteMessage(PrivateMessage $message): void
    {
        DB::transaction(function () use ($message): void {
            $conversation = $message->conversation;
            $message->delete();
            $conversation?->touch();
        });
    }
}
