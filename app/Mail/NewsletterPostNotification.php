<?php

namespace App\Mail;

use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class NewsletterPostNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param Collection<int, array{subscription: NewsletterSubscription, blog: Blog, posts: Collection<int, Post>}> $data
     */
    public function __construct(
        public Collection $data,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('newsletter.email.subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $email = $this->data->first()['subscription']->email;

        return new Content(
            markdown: 'emails.newsletter.posts',
            with: [
                'manageUrl' => URL::signedRoute('newsletter.manage', ['email' => $email]),
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
