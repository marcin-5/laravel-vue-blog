<?php

use App\Jobs\SendNewsletterNotification;
use App\Mail\NewsletterPostNotification;
use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('sends separate emails for multiple subscriptions by the same user', function () {
    Bus::fake();
    Mail::fake();

    $email = 'user@example.com';
    $blog1 = Blog::factory()->create(['name' => 'Blog One']);
    $blog2 = Blog::factory()->create(['name' => 'Blog Two']);

    $sendTime = '07:07';
    $this->travelTo(now()->startOfWeek()->setTimeFromTimeString($sendTime));

    NewsletterSubscription::factory()->create([
        'blog_id' => $blog1->id,
        'email' => $email,
        'frequency' => 'daily',
    ]);

    NewsletterSubscription::factory()->create([
        'blog_id' => $blog2->id,
        'email' => $email,
        'frequency' => 'daily',
    ]);

    // New post in blog 1
    Post::factory()->create([
        'blog_id' => $blog1->id,
        'published_at' => now()->subMinutes(10),
    ]);

    // New post in blog 2
    Post::factory()->create([
        'blog_id' => $blog2->id,
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
