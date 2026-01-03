<?php

use App\Mail\NewsletterPostNotification;
use App\Models\Blog;
use App\Models\NewsletterLog;
use App\Models\NewsletterSubscription;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('sends daily newsletter with only new posts', function () {
    Mail::fake();

    $blog = Blog::factory()->create();
    $subscription = NewsletterSubscription::factory()->create([
        'blog_id' => $blog->id,
        'frequency' => 'daily',
        'send_time' => now()->format('H:i'),
        'send_time_weekend' => now()->format('H:i'),
    ]);

    // Post sent before
    $oldPost = Post::factory()->create([
        'blog_id' => $blog->id,
        'published_at' => now()->subHours(5),
    ]);
    NewsletterLog::create([
        'newsletter_subscription_id' => $subscription->id,
        'post_id' => $oldPost->id,
        'sent_at' => now()->subHours(5),
    ]);

    // New post
    $newPost = Post::factory()->create([
        'blog_id' => $blog->id,
        'published_at' => now()->subMinutes(10),
    ]);

    // Post for another blog (should not be sent)
    $otherBlog = Blog::factory()->create();
    Post::factory()->create([
        'blog_id' => $otherBlog->id,
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

    $blog = Blog::factory()->create();
    NewsletterSubscription::factory()->create([
        'blog_id' => $blog->id,
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

    $blog = Blog::factory()->create();
    $subscription = NewsletterSubscription::factory()->create([
        'blog_id' => $blog->id,
        'frequency' => 'daily',
        'send_time' => now()->format('H:i'),
    ]);

    // Post older than a day
    Post::factory()->create([
        'blog_id' => $blog->id,
        'published_at' => now()->subDays(2),
    ]);

    Mail::assertNothingSent();
});

it('does not send duplicate emails when user has multiple subscriptions for the same time', function () {
    Mail::fake();

    $email = 'duplicate@example.com';
    $now = now();
    $time = $now->format('H:i');

    $blog1 = Blog::factory()->create(['name' => 'Blog 1']);
    $blog2 = Blog::factory()->create(['name' => 'Blog 2']);

    NewsletterSubscription::factory()->create([
        'email' => $email,
        'blog_id' => $blog1->id,
        'frequency' => 'daily',
        'send_time' => $time,
        'send_time_weekend' => $time,
    ]);

    NewsletterSubscription::factory()->create([
        'email' => $email,
        'blog_id' => $blog2->id,
        'frequency' => 'daily',
        'send_time' => $time,
        'send_time_weekend' => $time,
    ]);

    Post::factory()->create([
        'blog_id' => $blog1->id,
        'published_at' => $now->copy()->subMinutes(10),
    ]);

    Post::factory()->create([
        'blog_id' => $blog2->id,
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
