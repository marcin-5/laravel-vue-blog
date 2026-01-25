<?php

use App\Models\NewsletterSubscription;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard displays latest unique newsletter subscriptions for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $blog1 = createBlog(['name' => 'Blog 1'], $admin);
    $blog2 = createBlog(['name' => 'Blog 2'], $admin);

    // Create subscriptions
    NewsletterSubscription::factory()->create([
        'email' => 'user1@example.com',
        'blog_id' => $blog1->id,
        'created_at' => now()->subDays(1),
        'frequency' => 'daily',
    ]);

    NewsletterSubscription::factory()->create([
        'email' => 'user1@example.com',
        'blog_id' => $blog2->id,
        'created_at' => now(),
        'frequency' => 'daily',
    ]);

    NewsletterSubscription::factory()->create([
        'email' => 'user2@example.com',
        'blog_id' => $blog1->id,
        'created_at' => now()->subDays(2),
        'frequency' => 'daily',
    ]);

    $this->actingAs($admin);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn(Assert $page) => $page
        ->component('app/Dashboard')
        ->has('newsletterSubscriptions', 2)
        ->where('newsletterSubscriptions.0.email', 'user1@example.com')
        ->where('newsletterSubscriptions.0.subscriptions', [
            ['blog' => 'Blog 2', 'frequency' => 'daily'],
            ['blog' => 'Blog 1', 'frequency' => 'daily'],
        ])
        ->where('newsletterSubscriptions.1.email', 'user2@example.com')
        ->where('newsletterSubscriptions.1.subscriptions', [
            ['blog' => 'Blog 1', 'frequency' => 'daily'],
        ]),
    );
});

test('dashboard does not display newsletter subscriptions for non-admin', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $blog = createBlog([], $user);

    NewsletterSubscription::factory()->create([
        'email' => 'user1@example.com',
        'blog_id' => $blog->id,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn(Assert $page) => $page
        ->component('app/Dashboard')
        ->where('newsletterSubscriptions', []),
    );
});
