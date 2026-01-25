<?php

use App\Mail\NewsletterPostNotification;
use App\Models\NewsletterLog;
use Illuminate\Support\Facades\Mail;

it('sends daily newsletter with only new posts', function () {
    Mail::fake();

    $blog = createBlog();
    $subscription = createSubscription($blog, [
        'frequency' => 'daily',
        'send_time' => now()->format('H:i'),
        'send_time_weekend' => now()->format('H:i'),
    ]);

    // Post sent before
    $oldPost = createPost($blog, [
        'published_at' => now()->subHours(5),
    ]);
    NewsletterLog::create([
        'newsletter_subscription_id' => $subscription->id,
        'post_id' => $oldPost->id,
        'sent_at' => now()->subHours(5),
    ]);

    // New post
    $newPost = createPost($blog, [
        'published_at' => now()->subMinutes(10),
    ]);

    // Post for another blog (should not be sent)
    $otherBlog = createBlog();
    createPost($otherBlog, [
        'published_at' => now()->subMinutes(5),
    ]);

    $this->artisan('newsletter:send daily')
        ->expectsOutputToContain('Processing 1 subscriptions...')
        ->assertSuccessful();

    Mail::assertSent(NewsletterPostNotification::class, function ($mail) use ($subscription, $newPost) {
        return $mail->hasTo($subscription->email) &&
            $mail->data->first()['posts']->count() === 1 &&
            $mail->data->first()['posts']->first()->id === $newPost->id;
    });

    expect(NewsletterLog::count())->toBe(2); // old one + new one
});

it('does not send newsletter if no new posts', function () {
    Mail::fake();

    $blog = createBlog();
    createSubscription($blog, [
        'frequency' => 'daily',
        'send_time' => now()->format('H:i'),
    ]);

    $this->artisan('newsletter:send daily')
        ->expectsOutputToContain('Processing 1 subscriptions...')
        ->assertSuccessful();

    Mail::assertNothingSent();
});

it('filters posts by frequency', function () {
    Mail::fake();

    $blog = createBlog();
    $subscription = createSubscription($blog, [
        'frequency' => 'daily',
        'send_time' => now()->format('H:i'),
    ]);

    // Post older than a day
    createPost($blog, [
        'published_at' => now()->subDays(2),
    ]);

    Mail::assertNothingSent();
});

it('does not send duplicate emails when user has multiple subscriptions for the same time', function () {
    Mail::fake();

    $email = 'duplicate@example.com';
    $now = now();
    $time = $now->format('H:i');

    $user = createUser();
    $blog1 = createBlog(['name' => 'Blog 1'], $user);
    $blog2 = createBlog(['name' => 'Blog 2'], $user);

    createSubscription($blog1, [
        'email' => $email,
        'frequency' => 'daily',
        'send_time' => $time,
        'send_time_weekend' => $time,
    ]);

    createSubscription($blog2, [
        'email' => $email,
        'frequency' => 'daily',
        'send_time' => $time,
        'send_time_weekend' => $time,
    ]);

    createPost($blog1, [
        'published_at' => $now->copy()->subMinutes(10),
    ]);

    createPost($blog2, [
        'published_at' => $now->copy()->subMinutes(10),
    ]);

    $this->artisan('newsletter:send daily')
        ->assertSuccessful();

    // Assert that only ONE email was sent to this address
    Mail::assertSent(NewsletterPostNotification::class, 1);

    Mail::assertSent(NewsletterPostNotification::class, function ($mail) use ($email) {
        return $mail->hasTo($email) && $mail->data->count() === 2;
    });
});
