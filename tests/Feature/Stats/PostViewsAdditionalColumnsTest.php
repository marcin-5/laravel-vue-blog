<?php

use App\Enums\StatsRange;
use App\Enums\StatsSort;
use App\Models\AnonymousView;
use App\Models\Blog;
use App\Models\BotView;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Models\UserAgent;
use App\Services\StatsCriteria;
use App\Services\StatsService;

it('includes bot and anonymous views in post stats', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create();
    $post = Post::factory()->for($blog)->create();

    $userAgent = UserAgent::factory()->create(['name' => 'Test Bot']);

    PageView::query()->create([
        'viewable_type' => $post->getMorphClass(),
        'viewable_id' => $post->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'UA',
    ]);

    // Dodaj widoki botów
    BotView::factory()->create([
        'viewable_type' => $post->getMorphClass(),
        'viewable_id' => $post->id,
        'user_agent_id' => $userAgent->id,
        'hits' => 10,
        'last_seen_at' => now()->subDay(),
        'created_at' => now()->subDay(),
    ]);

    AnonymousView::factory()->create([
        'viewable_type' => $post->getMorphClass(),
        'viewable_id' => $post->id,
        'user_agent_id' => $userAgent->id,
        'hits' => 5,
        'last_seen_at' => now()->subDay(),
        'created_at' => now()->subDay(),
    ]);

    $service = app(StatsService::class);
    $criteria = new StatsCriteria(
        range: StatsRange::Week,
        bloggerId: null,
        blogId: $blog->id,
        limit: null,
        sort: StatsSort::ViewsDesc,
    );

    $rows = $service->postViews($criteria);

    expect($rows)->toHaveCount(1);
    $row = $rows->first();

    expect($row)->toHaveKey('bot_views')
        ->and($row)->toHaveKey('anonymous_views')
        ->and($row['bot_views'])->toBe(10)
        ->and($row['anonymous_views'])->toBe(5);
});
