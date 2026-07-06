<?php

use App\Jobs\StorePageView;
use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Cache::flush();

    $this->owner = User::factory()->create();
    $this->blog = Blog::factory()->for($this->owner)->create(['is_published' => true]);
    $this->post = Post::factory()->for($this->blog)->create();
    $this->url = "http://{$this->blog->slug}." . config('app.domain') . "/{$this->post->slug}";
});

it('counts first logged-in visit to a new post when cookie consent is accepted', function () {
    $user = User::factory()->create();

    $this->withUnencryptedCookie('cookie_consent', 'accepted')
        ->actingAs($user)
        ->get($this->url);

    Queue::assertPushed(StorePageView::class, 1);
});

it(
    'counts unique views for two different logged-in users on the same device when cookie consent is accepted',
    function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // First user visits
        $this->withUnencryptedCookie('cookie_consent', 'accepted')
            ->actingAs($userA)
            ->get($this->url);

        // Second user visits (same IP/UA implied by test client)
        $this->withUnencryptedCookie('cookie_consent', 'accepted')
            ->actingAs($userB)
            ->get($this->url);

        Queue::assertPushed(StorePageView::class, 2);
    },
);

it('does not count visits when cookie consent is rejected or missing', function () {
    $user = User::factory()->create();

    // Rejected consent
    $this->withUnencryptedCookie('cookie_consent', 'rejected')
        ->actingAs($user)
        ->get($this->url);

    // No consent cookie at all
    $this->actingAs($user)
        ->get($this->url);

    Queue::assertPushed(StorePageView::class, 0);
});
