<?php

use App\Jobs\StorePageView;
use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('does not track page views for bots', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $post = Post::factory()->for($blog)->create();

    // Request from a normal user
    $this->get("/{$blog->slug}/{$post->slug}", [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);

    Queue::assertPushed(StorePageView::class, 1);

    // Request from a bot (Googlebot)
    $this->get("/{$blog->slug}/{$post->slug}", [
        'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
    ]);

    // Should still be 1, because the bot request should be ignored
    Queue::assertPushed(StorePageView::class, 1);

    // Request from another bot (Bingbot)
    $this->get("/{$blog->slug}/{$post->slug}", [
        'User-Agent' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'
    ]);

    Queue::assertPushed(StorePageView::class, 1);
});
