<?php

use App\Jobs\StoreBotView;
use App\Jobs\StorePageView;
use App\Models\Blog;
use App\Models\BotView;
use App\Models\Post;
use App\Models\User;
use App\Models\UserAgent;
use Illuminate\Support\Facades\Queue;

it('tracks page views for bots in separate table', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $post = Post::factory()->for($blog)->create();

    // Request from a normal user
    $this->withCookie('visitor_id', 'test-visitor-id')
        ->get("/{$blog->slug}/{$post->slug}", [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);

    Queue::assertPushed(StorePageView::class, 1);
    Queue::assertNotPushed(StoreBotView::class);

    // Request from a bot (Googlebot)
    $botUserAgent = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
    $this->withCookie('visitor_id', 'test-visitor-id')
        ->get("/{$blog->slug}/{$post->slug}", [
            'User-Agent' => $botUserAgent,
        ]);

    // Should still be 1 StorePageView, but 1 StoreBotView should be pushed
    Queue::assertPushed(StorePageView::class, 1);
    Queue::assertPushed(StoreBotView::class, function (StoreBotView $job) use ($post, $botUserAgent) {
        $reflection = new ReflectionProperty($job, 'data');
        $data = $reflection->getValue($job);

        return $data['viewable_type'] === $post->getMorphClass()
            && $data['viewable_id'] === $post->getKey()
            && $data['user_agent'] === $botUserAgent;
    });
});

it('correctly updates bot views in database', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);

    $botUserAgent = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    // First visit
    dispatch(new StoreBotView([
        'viewable_type' => $blog->getMorphClass(),
        'viewable_id' => $blog->getKey(),
        'user_agent' => $botUserAgent,
    ]));

    $this->assertDatabaseHas('bot_views', [
        'viewable_type' => $blog->getMorphClass(),
        'viewable_id' => $blog->getKey(),
        'hits' => 1,
    ]);

    $userAgentId = UserAgent::where('name', $botUserAgent)->firstOrFail()->id;
    $this->assertDatabaseHas('bot_views', ['user_agent_id' => $userAgentId]);

    $lastSeenAt = BotView::first()->last_seen_at;

    // Second visit (same bot, same page)
    sleep(1); // Ensure last_seen_at changes
    dispatch(new StoreBotView([
        'viewable_type' => $blog->getMorphClass(),
        'viewable_id' => $blog->getKey(),
        'user_agent' => $botUserAgent,
    ]));

    $botView = BotView::first();
    expect($botView->hits)->toBe(2)
        ->and($botView->last_seen_at->gt($lastSeenAt))->toBeTrue();

    // Visit from another bot
    $otherBotUA = 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)';
    dispatch(new StoreBotView([
        'viewable_type' => $blog->getMorphClass(),
        'viewable_id' => $blog->getKey(),
        'user_agent' => $otherBotUA,
    ]));

    expect(BotView::count())->toBe(2);
    $this->assertDatabaseHas('bot_views', [
        'user_agent_id' => UserAgent::where('name', $otherBotUA)->firstOrFail()->id,
        'hits' => 1,
    ]);
});
