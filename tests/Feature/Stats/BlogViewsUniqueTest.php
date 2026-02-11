<?php

use App\Enums\StatsRange;
use App\Enums\StatsSort;
use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Services\StatsCriteria;
use App\Services\StatsService;

it('counts unique blog and post views in blogViews', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $postA = Post::factory()->for($blog)->create();
    $postB = Post::factory()->for($blog)->create();

    // Same logged in user views blog page twice and both posts
    $viewer = User::factory()->create();

    // Blog page views (viewable = blog)
    PageView::query()->create([
        'user_id' => $viewer->id,
        'viewable_type' => new Blog()->getMorphClass(),
        'viewable_id' => $blog->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'UA',
    ]);
    PageView::query()->create([
        'user_id' => $viewer->id,
        'viewable_type' => new Blog()->getMorphClass(),
        'viewable_id' => $blog->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'UA',
    ]);

    // Post views
    foreach ([$postA, $postB] as $p) {
        PageView::query()->create([
            'user_id' => $viewer->id,
            'viewable_type' => new Post()->getMorphClass(),
            'viewable_id' => $p->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'UA',
        ]);
    }

    // Anonymous different visitor views one post and blog page
    PageView::query()->create([
        'user_id' => null,
        'visitor_id' => 'anon-1',
        'viewable_type' => new Blog()->getMorphClass(),
        'viewable_id' => $blog->id,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'UA2',
    ]);
    PageView::query()->create([
        'user_id' => null,
        'visitor_id' => 'anon-1',
        'viewable_type' => new Post()->getMorphClass(),
        'viewable_id' => $postA->id,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'UA2',
    ]);

    $service = app(StatsService::class);
    $criteria = new StatsCriteria(
        range: StatsRange::Week,
        bloggerId: null,
        blogId: null,
        limit: null,
        sort: StatsSort::ViewsDesc,
    );

    $rows = $service->blogViews($criteria);
    $row = $rows->firstWhere('blog_id', $blog->id);

    // Blog views: 2 by user + 1 by anon = 3 total, but unique viewers = 2
    // Post views totals = 3 (user viewed 2 posts + anon viewed 1 post)
    // Unique post viewers = 2 (user + anon)
    expect($row['views'])->toBe(3)
        ->and($row['unique_views'])->toBe(2)
        ->and($row['post_views'])->toBe(3)
        ->and($row['unique_post_views'])->toBe(2);
});
