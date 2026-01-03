<?php

use App\Mail\NewsletterPostNotification;
use App\Models\Blog;
use App\Models\NewsletterSubscription;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('it saves send_time_weekend to newsletter subscriptions', function () {
    $blog = Blog::factory()->create(['is_published' => true]);
    $email = 'test@example.com';

    $payload = [
        'email' => $email,
        'subscriptions' => [
            [
                'blog_id' => $blog->id,
                'frequency' => 'daily',
                'send_time' => '08:00',
                'send_time_weekend' => '10:00',
            ]
        ]
    ];

    $response = $this->post(route('newsletter.store'), $payload);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('newsletter_subscriptions', [
        'email' => $email,
        'blog_id' => $blog->id,
        'frequency' => 'daily',
        'send_time' => '08:00',
        'send_time_weekend' => '10:00',
    ]);
});

test('it sends newsletter at correct time on weekdays', function () {
    Mail::fake();
    $blog = Blog::factory()->create(['is_published' => true]);

    NewsletterSubscription::factory()->create([
        'email' => 'user@example.com',
        'blog_id' => $blog->id,
        'frequency' => 'daily',
        'send_time' => '08:00',
        'send_time_weekend' => '10:00',
    ]);

    // Monday 08:00
    Carbon::setTestNow(Carbon::parse('2026-01-05 08:00:00'));

    // Create post AFTER setting test time or ensure published_at is relative to it
    Post::factory()->create([
        'blog_id' => $blog->id,
        'published_at' => now()->subHour(),
        'is_published' => true,
    ]);

    Artisan::call('newsletter:send');
    Mail::assertSent(NewsletterPostNotification::class);

    Mail::fake();
    // Monday 07:59 (Should not send)
    Carbon::setTestNow(Carbon::parse('2026-01-05 07:59:00'));
    Artisan::call('newsletter:send');
    Mail::assertNotSent(NewsletterPostNotification::class);
});

test('it sends newsletter at correct time on weekends', function () {
    Mail::fake();
    $blog = Blog::factory()->create(['is_published' => true]);

    NewsletterSubscription::factory()->create([
        'email' => 'user@example.com',
        'blog_id' => $blog->id,
        'frequency' => 'daily',
        'send_time' => '08:00',
        'send_time_weekend' => '10:00',
    ]);

    // Saturday 10:00
    Carbon::setTestNow(Carbon::parse('2026-01-03 10:00:00'));

    Post::factory()->create([
        'blog_id' => $blog->id,
        'published_at' => now()->subHour(),
        'is_published' => true,
    ]);

    Artisan::call('newsletter:send');
    Mail::assertSent(NewsletterPostNotification::class);

    Mail::fake();
    // Saturday 08:00 (Should not send, even if weekday time is 08:00)
    Carbon::setTestNow(Carbon::parse('2026-01-03 08:00:00'));
    Artisan::call('newsletter:send');
    Mail::assertNotSent(NewsletterPostNotification::class);
});
