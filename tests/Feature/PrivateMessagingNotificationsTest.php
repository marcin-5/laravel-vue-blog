<?php

use App\Jobs\SendPrivateMessageNotification;
use App\Mail\PrivateMessageNotification;
use App\Models\PrivateConversation;
use App\Models\PrivateConversationParticipant;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

function notificationConversation(User $initiator, User $owner): PrivateConversation
{
    $conversation = PrivateConversation::factory()->create([
        'initiator_id' => $initiator->id,
        'owner_id' => $owner->id,
    ]);

    PrivateConversationParticipant::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
    ]);
    PrivateConversationParticipant::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $owner->id,
    ]);

    return $conversation;
}

it('queues delayed notifications for new, replied to, and edited messages', function () {
    $this->travelTo('2026-09-26 20:00:00');
    Queue::fake();
    $initiator = User::factory()->create();
    $owner = User::factory()->create();
    $conversation = notificationConversation($initiator, $owner);
    $message = PrivateMessage::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
    ]);

    app(\App\Services\PrivateConversationService::class)->reply($conversation, $owner, 'Reply');
    app(\App\Services\PrivateConversationService::class)->updateMessage($message, 'Updated');

    Queue::assertPushed(SendPrivateMessageNotification::class, 2);
    Queue::assertPushed(SendPrivateMessageNotification::class, function (SendPrivateMessageNotification $job) use ($message): bool {
        return $job->messageId === $message->id
            && $job->notificationVersion === 2
            && $job->delay?->equalTo(now()->addMinutes(15));
    });
});

it('sends the latest message only when the version and preference are current', function () {
    Mail::fake();
    $initiator = User::factory()->create();
    $owner = User::factory()->create();
    $conversation = notificationConversation($initiator, $owner);
    $message = PrivateMessage::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
        'content' => 'Latest content',
    ]);

    (new SendPrivateMessageNotification($message->id, 1))->handle();

    Mail::assertSent(PrivateMessageNotification::class, fn(PrivateMessageNotification $mail): bool => $mail->hasTo($owner->email));
    expect($message->refresh()->notification_sent_version)->toBe(1);
});

it('does not send stale or disabled notifications and is idempotent', function () {
    Mail::fake();
    $initiator = User::factory()->create();
    $owner = User::factory()->create();
    $conversation = notificationConversation($initiator, $owner);
    $message = PrivateMessage::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
    ]);

    $message->update(['notification_version' => 2]);
    (new SendPrivateMessageNotification($message->id, 1))->handle();
    $conversation->participants()->where('user_id', $owner->id)->update(['email_notifications' => false]);
    (new SendPrivateMessageNotification($message->id, 2))->handle();
    (new SendPrivateMessageNotification($message->id, 2))->handle();

    Mail::assertNothingSent();
    expect($message->refresh()->notification_sent_version)->toBe(2);
});

it('renders translated subject and message content', function () {
    $initiator = User::factory()->create(['name' => 'Jan']);
    $owner = User::factory()->create();
    $conversation = notificationConversation($initiator, $owner);
    $message = PrivateMessage::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
        'content' => 'Treść wiadomości',
    ]);

    $mail = new PrivateMessageNotification($message->load(['conversation', 'user']));

    $mail->assertSeeInHtml('Treść wiadomości');
    $mail->assertSeeInHtml('Jan');
    expect($mail->envelope()->subject)->toContain($conversation->subject);
});
