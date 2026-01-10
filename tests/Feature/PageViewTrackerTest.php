<?php

use App\Jobs\StorePageView;
use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    Queue::fake();
    Cache::flush();

    $this->owner = User::factory()->create();
    $this->blog = Blog::factory()->for($this->owner)->create(['is_published' => true]);
    $this->post = Post::factory()->for($this->blog)->create();
});

it('delays tracking for a new visitor without a cookie', function () {
    // First request - no cookie
    $response = $this->get("/{$this->blog->slug}/{$this->post->slug}");

    // Should NOT push to queue immediately
    Queue::assertNothingPushed();

    $cookie = $response->getCookie('visitor_id');
    expect($cookie)->not->toBeNull();
    $visitorId = $cookie->getValue();

    // Second request - with cookie
    $this->withCookie('visitor_id', $visitorId)
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    // Now it should push 2 jobs: one for the pending first visit, one for the current visit
    Queue::assertPushed(StorePageView::class, 2);
});

it('tracks immediately if X-Visitor-Id header is present (verified by JS)', function () {
    $visitorId = (string)Str::uuid();

    $this->withHeaders(['X-Visitor-Id' => $visitorId])
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    // Should push immediately because header proves JS execution (LocalStorage)
    Queue::assertPushed(StorePageView::class, 1);
});

it('does not duplicate track if visit is within block period', function () {
    $visitorId = (string)Str::uuid();

    // First tracked visit
    $this->withHeaders(['X-Visitor-Id' => $visitorId])
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    Queue::assertPushed(StorePageView::class, 1);

    // Immediate second visit
    $this->withCookie('visitor_id', $visitorId)
        ->withHeaders(['X-Visitor-Id' => $visitorId])
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    // Should still be 1
    Queue::assertPushed(StorePageView::class, 1);
});
