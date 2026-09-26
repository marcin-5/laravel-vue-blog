<?php

namespace App\Jobs;

use App\Mail\PrivateMessageNotification;
use App\Models\PrivateMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendPrivateMessageNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $messageId,
        public int $notificationVersion,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $recipients = DB::transaction(function (): array {
            $message = PrivateMessage::query()
                ->with(['conversation', 'conversation.participants.user'])
                ->lockForUpdate()
                ->find($this->messageId);

            if ($message === null
                || $message->notification_version !== $this->notificationVersion
                || $message->notification_sent_version === $this->notificationVersion) {
                return [];
            }

            $recipients = $message->conversation->participants
                ->filter(fn($participant): bool => $participant->user_id !== $message->user_id
                    && $participant->email_notifications
                    && $participant->user !== null)
                ->map(fn($participant): string => $participant->user->email)
                ->values()
                ->all();

            $message->forceFill([
                'notification_sent_version' => $this->notificationVersion,
            ])->save();

            return $recipients;
        });

        if ($recipients === []) {
            return;
        }

        $message = PrivateMessage::query()
            ->with(['conversation', 'user'])
            ->find($this->messageId);

        if ($message === null) {
            return;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new PrivateMessageNotification($message));
        }
    }
}
