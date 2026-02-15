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

it('tracks immediately for a new visitor without a cookie', function () {
    // First request - no visitor_id cookie, but cookie consent accepted
    $response = $this->withUnencryptedCookie('cookie_consent', 'accepted')
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    // Should push to queue immediately (fingerprint-based identification)
    Queue::assertPushed(StorePageView::class, 1);

    $cookie = $response->getCookie('visitor_id');
    expect($cookie)->not->toBeNull();
});

it('tracks immediately if X-Visitor-Id header is present (verified by JS)', function () {
    $visitorId = (string) Str::uuid();

    $this->withHeaders(['X-Visitor-Id' => $visitorId])
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    // Should push immediately because header proves JS execution (LocalStorage)
    Queue::assertPushed(StorePageView::class, 1);
});

it('does not duplicate track if visit is within block period', function () {
    $visitorId = (string) Str::uuid();

    // First tracked visit
    $this->withUnencryptedCookie('cookie_consent', 'accepted')
        ->withHeaders(['X-Visitor-Id' => $visitorId])
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    Queue::assertPushed(StorePageView::class, 1);

    // Immediate second visit
    $this->withUnencryptedCookie('cookie_consent', 'accepted')
        ->withCookie('visitor_id', $visitorId)
        ->withHeaders(['X-Visitor-Id' => $visitorId])
        ->get("/{$this->blog->slug}/{$this->post->slug}");

    // Should still be 1
    Queue::assertPushed(StorePageView::class, 1);
});
