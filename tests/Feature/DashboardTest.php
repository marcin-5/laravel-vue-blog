<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard and receive translations', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(fn(Assert $page) => $page
        ->component('app/Dashboard')
        ->has('translations')
        ->has('translations.locale')
        ->has('translations.messages')
        ->where('translations.messages.dashboard.title', 'Dashboard')
        ->where('translations.messages.admin.dashboard.recent_subscriptions', 'Recent subscriptions')
        ->where('translations.messages.blogger.stats.timeline_title', 'Posts Timeline'),
    );
});
