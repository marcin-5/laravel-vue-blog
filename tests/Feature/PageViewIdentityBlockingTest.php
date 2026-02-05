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
});

it('counts first logged-in visit to a new post', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    Queue::assertPushed(StorePageView::class, 1);
});

it('counts unique views for two different logged-in users on the same device', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // First user visits
    $this->actingAs($userA)
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    // Second user visits (same IP/UA implied by test client)
    $this->actingAs($userB)
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    Queue::assertPushed(StorePageView::class, 2);
});
