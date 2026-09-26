<?php

namespace App\Mail;

use App\Models\PrivateMessage;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrivateMessageNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PrivateMessage $message,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('private_messaging.email.subject', [
                'subject' => $this->message->conversation->subject,
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.private-message',
            with: [
                'conversationUrl' => route('private-conversations.show', $this->message->conversation),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
