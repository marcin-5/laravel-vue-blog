<?php

use App\Enums\StatsRange;
use App\Enums\StatsSort;
use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Services\StatsCriteria;
use App\Services\StatsService;

it('counts unique post views separately from total views', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->for($user)->create();
    $post = Post::factory()->for($blog)->create();

    // Two views by the same logged-in user -> 2 views, 1 unique
    PageView::query()->create([
        'user_id' => $user->id,
        'viewable_type' => (new Post)->getMorphClass(),
        'viewable_id' => $post->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'UA',
    ]);
    PageView::query()->create([
        'user_id' => $user->id,
        'viewable_type' => new Post()->getMorphClass(),
        'viewable_id' => $post->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'UA',
    ]);

    // One anonymous visitor with cookie on device A
    PageView::query()->create([
        'user_id' => null,
        'visitor_id' => 'A-cookie',
        'viewable_type' => new Post()->getMorphClass(),
        'viewable_id' => $post->id,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'UA2',
    ]);

    // Another anonymous visitor on device B (different cookie & fingerprint)
    PageView::query()->create([
        'user_id' => null,
        'visitor_id' => 'B-cookie',
        'fingerprint' => 'finger-B',
        'viewable_type' => new Post()->getMorphClass(),
        'viewable_id' => $post->id,
        'ip_address' => '10.0.0.3',
        'user_agent' => 'UA3',
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
    expect($row['views'])->toBe(4)
        ->and($row['unique_views'])->toBe(3); // 1 user + 2 distinct anon visitors
});
