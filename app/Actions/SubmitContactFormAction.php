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
     */
    public function execute(array $data): void
    {
        Mail::to(config('mail.contact_to'))
            ->send(new ContactMessageMail($data));
    }
}
