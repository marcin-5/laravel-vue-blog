<?php

use App\Jobs\SendNewsletterNotification;
use App\Mail\NewsletterPostNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

it('sends separate emails for multiple subscriptions by the same user', function () {
    Bus::fake();
    Mail::fake();

    $email = 'user@example.com';
    $user = createUser();
    $blog1 = createBlog(['name' => 'Blog One'], $user);
    $blog2 = createBlog(['name' => 'Blog Two'], $user);

    $sendTime = '07:07';
    $this->travelTo(now()->startOfWeek()->setTimeFromTimeString($sendTime));

    createSubscription($blog1, [
        'email' => $email,
        'frequency' => 'daily',
    ]);

    createSubscription($blog2, [
        'email' => $email,
        'frequency' => 'daily',
    ]);

    // New post in blog 1
    createPost($blog1, [
        'published_at' => now()->subMinutes(10),
    ]);

    // New post in blog 2
    createPost($blog2, [
        'published_at' => now()->subMinutes(5),
    ]);

    $this->artisan('newsletter:send daily')
        ->assertSuccessful();

    Bus::assertDispatched(SendNewsletterNotification::class, function ($job) use ($email) {
        if ($job->email !== $email || $job->data->count() !== 2) {
            return false;
        }

        // Initiate the task manually to test email sending.
        $job->handle();

        return true;
    });

    // Now it should send only 1 email
    Mail::assertSent(NewsletterPostNotification::class, 1);
    Mail::assertSent(NewsletterPostNotification::class, function ($mail) use ($email) {
        return $mail->hasTo($email) && $mail->data->count() === 2;
    });
});
