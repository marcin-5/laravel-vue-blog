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
        ->expectsOutputToContain('Przetwarzanie 1 subskrypcji...')
        ->assertSuccessful();

    Mail::assertSent(NewsletterPostNotification::class, function ($mail) use ($subscription, $newPost) {
        return $mail->hasTo($subscription->email) &&
            $mail->posts->count() === 1 &&
            $mail->posts->first()->id === $newPost->id;
    });

    expect(NewsletterLog::count())->toBe(2); // old one + new one
});

it('does not send newsletter if no new posts', function () {
    Mail::fake();

    $blog = Blog::factory()->create();
    NewsletterSubscription::factory()->create([
        'blog_id' => $blog->id,
        'frequency' => 'daily',
    ]);

    $this->artisan('newsletter:send daily')
        ->expectsOutputToContain('Przetwarzanie 1 subskrypcji...')
        ->assertSuccessful();

    Mail::assertNothingSent();
});

it('filters posts by frequency', function () {
    Mail::fake();

    $blog = Blog::factory()->create();
    $subscription = NewsletterSubscription::factory()->create([
        'blog_id' => $blog->id,
        'frequency' => 'daily',
    ]);

    // Post older than a day
    Post::factory()->create([
        'blog_id' => $blog->id,
        'published_at' => now()->subDays(2),
    ]);

    $this->artisan('newsletter:send daily')
        ->assertSuccessful();

    Mail::assertNothingSent();
});
