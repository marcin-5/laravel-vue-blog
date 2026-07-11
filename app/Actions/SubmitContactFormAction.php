<?php

namespace App\Actions;

use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;

readonly class SubmitContactFormAction
{
    /**
     * Handle the contact form submission.
     *
     * @param array<string, string> $data
     * @param string|null $recipientEmail Custom recipient email; defaults to the configured contact address.
     * @param string|null $recipientName  Optional display name for the recipient.
     */
    public function execute(array $data, ?string $recipientEmail = null, ?string $recipientName = null): void
    {
        $email = $recipientEmail ?? config('mail.contact_to');

        Mail::to($email, $recipientName)
            ->send(new ContactMessageMail($data));
    }
}
