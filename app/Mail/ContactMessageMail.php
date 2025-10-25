<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build(): ContactMessageMail
    {
        return $this
            ->subject($this->data['subject'] ?? 'New contact message')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->markdown('emails.contact');
    }
}
