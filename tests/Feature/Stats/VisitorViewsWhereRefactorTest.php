<?php

use App\Enums\StatsRange;
use App\Enums\StatsSort;
use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Services\StatsCriteria;
use App\Services\StatsService;

it('filters visitors by blog via WHERE (blog page or its posts)', function () {
    $owner = User::factory()->create();
    $blog1 = Blog::factory()->for($owner)->create(['name' => 'Blog One']);
    $blog2 = Blog::factory()->for($owner)->create(['name' => 'Blog Two']);
    $post1 = Post::factory()->for($blog1)->create(['title' => 'P1']);
    $post2 = Post::factory()->for($blog2)->create(['title' => 'P2']);

    $blogClass = (new Blog)->getMorphClass();
    $postClass = (new Post)->getMorphClass();

    // Visitor v1 interacts only with blog1: once on blog page, once on a post within blog1
    PageView::query()->create([
        'visitor_id' => 'v1',
        'user_id' => null,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'UA-1',
        'viewable_type' => $blogClass,
        'viewable_id' => $blog1->id,
    ]);
    PageView::query()->create([
        'visitor_id' => 'v1',
        'user_id' => null,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'UA-1',
        'viewable_type' => $postClass,
        'viewable_id' => $post1->id,
    ]);

    // Visitor v2 interacts only with blog2
    PageView::query()->create([
        'visitor_id' => 'v2',
        'user_id' => null,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'UA-2',
        'viewable_type' => $blogClass,
        'viewable_id' => $blog2->id,
    ]);
    PageView::query()->create([
        'visitor_id' => 'v2',
        'user_id' => null,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'UA-2',
        'viewable_type' => $postClass,
        'viewable_id' => $post2->id,
    ]);

    $service = app(StatsService::class);
    $criteria = new StatsCriteria(
        range: StatsRange::Week,
        bloggerId: null,
        blogId: $blog1->id,
        limit: null,
        sort: StatsSort::ViewsDesc,
        visitorGroupBy: 'visitor_id',
        visitorType: 'all',
    );

    $rows = $service->visitorViews($criteria);
    expect($rows)->toHaveCount(1);
    $row = $rows->first();
    // v1 should be the only visitor, with 1 blog view and 1 post view within blog1
    expect($row['views'])->toBe(2)
        ->and($row['blog_views'])->toBe(1)
        ->and($row['post_views'])->toBe(1);
});

it('applies limit after filtering', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create();
    $post = Post::factory()->for($blog)->create();

    $blogClass = (new Blog)->getMorphClass();
    $postClass = (new Post)->getMorphClass();

    foreach (range(1, 3) as $i) {
        PageView::query()->create([
            'visitor_id' => 'v' . $i,
            'user_id' => null,
            'ip_address' => '10.0.0.' . $i,
            'user_agent' => 'UA-' . $i,
            'viewable_type' => $blogClass,
            'viewable_id' => $blog->id,
        ]);
        PageView::query()->create([
            'visitor_id' => 'v' . $i,
            'user_id' => null,
            'ip_address' => '10.0.0.' . $i,
            'user_agent' => 'UA-' . $i,
            'viewable_type' => $postClass,
            'viewable_id' => $post->id,
        ]);
    }

    $service = app(StatsService::class);
    $criteria = new StatsCriteria(
        range: StatsRange::Week,
        blogId: $blog->id,
        limit: 1,
        sort: StatsSort::ViewsDesc,
    );

    $rows = $service->visitorViews($criteria);
    expect($rows->count())->toBe(1);
});
