<?php

use App\Enums\StatsRange;
use App\Enums\StatsSort;
use App\Models\Blog;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Services\StatsCriteria;
use App\Services\StatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function createBlogWithOwner(string $role = User::ROLE_BLOGGER, array $blogAttributes = []): array
{
    $owner = User::factory()->create(['role' => $role]);
    $blog = Blog::factory()->create(array_merge(['user_id' => $owner->id], $blogAttributes));

    return [$owner, $blog];
}

function createPageView(string $viewableType, int $viewableId, array $extra = []): PageView
{
    return PageView::create(array_merge([
        'viewable_type' => $viewableType,
        'viewable_id' => $viewableId,
        'ip_address' => '127.0.0.1',
        'session_id' => 'sess_' . uniqid(),
        'user_agent' => 'test',
    ], $extra));
}

it('aggregates blog views and post views with default sorting and limits', function () {
    Carbon::setTestNow('2025-01-15 12:00:00');

    [$owner1, $blog1] = createBlogWithOwner();
    [$owner2, $blog2] = createBlogWithOwner();

    $post1 = Post::factory()->create(['blog_id' => $blog1->id, 'title' => 'Post A']);
    $post2 = Post::factory()->create(['blog_id' => $blog2->id, 'title' => 'Post B']);

    // Two blog views for blog1, one for blog2 within range
    $blogMorph = $blog1->getMorphClass();
    foreach (
        [
            ['viewable_id' => $blog1->id],
            ['viewable_id' => $blog1->id],
            ['viewable_id' => $blog2->id],
        ] as $data
    ) {
        createPageView($blogMorph, $data['viewable_id']);
    }

    // Post views
    $postMorph = $post1->getMorphClass();
    foreach (
        [
            ['viewable_id' => $post1->id],
            ['viewable_id' => $post1->id],
            ['viewable_id' => $post2->id],
        ] as $data
    ) {
        createPageView($postMorph, $data['viewable_id']);
    }

    $service = new StatsService;
    $criteria = new StatsCriteria(range: StatsRange::Week, limit: 10);

    $results = $service->blogViews($criteria);

    expect($results)->toHaveCount(2);

    // Default sort is ViewsDesc, so blog1 (2 views) comes first
    $first = $results[0];
    $second = $results[1];

    expect($first['blog_id'])->toBe($blog1->id)
        ->and($first['owner_id'])->toBe($owner1->id)
        ->and($first['views'])->toBe(2)
        // blog1 has two post views from post1
        ->and($first['post_views'])->toBe(2)
        ->and($second['blog_id'])->toBe($blog2->id)
        ->and($second['owner_id'])->toBe($owner2->id)
        ->and($second['views'])->toBe(1)
        ->and($second['post_views'])->toBe(1);
});

it('filters blog stats by blogger and blog and applies name sorting', function () {
    Carbon::setTestNow('2025-01-15 12:00:00');

    [$owner1, $blog1] = createBlogWithOwner(blogAttributes: ['name' => 'Alpha']);
    [, $blog2] = createBlogWithOwner(blogAttributes: ['name' => 'Zulu']);

    $blogMorph = $blog1->getMorphClass();

    // views for both blogs
    foreach (
        [
            ['viewable_id' => $blog1->id],
            ['viewable_id' => $blog2->id],
        ] as $data
    ) {
        createPageView($blogMorph, $data['viewable_id']);
    }

    $service = new StatsService;
    $criteria = new StatsCriteria(
        range: StatsRange::Week,
        bloggerId: $owner1->id,
        blogId: $blog1->id,
        limit: null,
        sort: StatsSort::NameAsc,
    );

    $results = $service->blogViews($criteria);

    expect($results)->toHaveCount(1);

    $row = $results->first();
    expect($row['blog_id'])->toBe($blog1->id)
        ->and($row['owner_id'])->toBe($owner1->id)
        ->and($row['name'])->toBe('Alpha');
});

it('limits number of returned blogs to at least one when size is small positive', function () {
    Carbon::setTestNow('2025-01-15 12:00:00');

    [$owner1, $blog1] = createBlogWithOwner();
    [$owner2, $blog2] = createBlogWithOwner();

    $blogMorph = $blog1->getMorphClass();
    foreach (
        [
            ['viewable_id' => $blog1->id],
            ['viewable_id' => $blog2->id],
        ] as $data
    ) {
        createPageView($blogMorph, $data['viewable_id']);
    }

    $service = new StatsService;
    $criteria = new StatsCriteria(range: StatsRange::Week, limit: 1);

    $results = $service->blogViews($criteria);

    expect($results)->toHaveCount(1);
});

it('aggregates post views with blogger, blog, range and sorting', function () {
    Carbon::setTestNow('2025-01-15 12:00:00');

    [$owner, $blog] = createBlogWithOwner();
    $otherBlog = Blog::factory()->create();

    $post1 = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'A Post']);
    $post2 = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'B Post']);
    $otherPost = Post::factory()->create(['blog_id' => $otherBlog->id, 'title' => 'Other']);

    $postMorph = $post1->getMorphClass();

    foreach (
        [
            ['viewable_id' => $post1->id],
            ['viewable_id' => $post1->id],
            ['viewable_id' => $post2->id],
            // other blog post should be ignored when filtering by blogger and blog
            ['viewable_id' => $otherPost->id],
        ] as $data
    ) {
        createPageView($postMorph, $data['viewable_id']);
    }

    // one old view outside of Week range
    $oldView = createPageView($postMorph, $post1->id, ['session_id' => 'old']);
    $oldView->forceFill(['created_at' => now()->subMonths(2)])->save();

    $service = new StatsService;
    $criteria = new StatsCriteria(
        range: StatsRange::Week,
        bloggerId: $owner->id,
        blogId: $blog->id,
        limit: 10,
        sort: StatsSort::ViewsDesc,
    );

    $results = $service->postViews($criteria);

    // Only posts from blogger's blog and within range should be counted
    expect($results)->toHaveCount(2);

    $first = $results[0];
    $second = $results[1];

    expect($first['post_id'])->toBe($post1->id)
        ->and($first['views'])->toBe(2)
        ->and($second['post_id'])->toBe($post2->id)
        ->and($second['views'])->toBe(1);
});

it('applies title sorting and respects post limit', function () {
    Carbon::setTestNow('2025-01-15 12:00:00');

    [, $blog] = createBlogWithOwner();

    $postA = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'Alpha']);
    $postB = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'Beta']);
    $postC = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'Gamma']);

    $postMorph = $postA->getMorphClass();

    // One view for each post
    foreach ([$postA, $postB, $postC] as $post) {
        createPageView($postMorph, $post->id);
    }

    $service = new StatsService;
    $criteria = new StatsCriteria(
        range: StatsRange::Week,
        blogId: $blog->id,
        limit: 2,
        sort: StatsSort::TitleAsc,
    );

    $results = $service->postViews($criteria);

    // Limited to 2 posts sorted by title
    expect($results)->toHaveCount(2)
        ->and($results[0]['title'])->toBe('Alpha')
        ->and($results[1]['title'])->toBe('Beta');
});

it('correctly counts post views when blog has multiple blog views', function () {
    Carbon::setTestNow('2025-01-15 12:00:00');

    [$owner, $blog] = createBlogWithOwner();

    // Create 2 posts with different view counts
    $post1 = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'Post 1']);
    $post2 = Post::factory()->create(['blog_id' => $blog->id, 'title' => 'Post 2']);

    // Create 4 views for post1
    $postMorph = $post1->getMorphClass();
    for ($i = 0; $i < 4; $i++) {
        createPageView($postMorph, $post1->id);
    }

    // Create 2 views for post2
    for ($i = 0; $i < 2; $i++) {
        createPageView($postMorph, $post2->id);
    }

    // Create 3 direct blog views (this should not affect post_views count)
    $blogMorph = $blog->getMorphClass();
    for ($i = 0; $i < 3; $i++) {
        createPageView($blogMorph, $blog->id);
    }

    $service = new StatsService;
    $criteria = new StatsCriteria(range: StatsRange::Week);

    $results = $service->blogViews($criteria);

    expect($results)->toHaveCount(1);

    $blogStats = $results[0];
    expect($blogStats['blog_id'])->toBe($blog->id)
        ->and($blogStats['views'])->toBe(3) // 3 direct blog views
        ->and($blogStats['post_views'])->toBe(6); // 4 + 2 = 6 post views total
});

it('aggregates visitor views with blog filter and sorts by post views', function () {
    Carbon::setTestNow('2025-01-15 12:00:00');

    [$owner1, $blog1] = createBlogWithOwner(blogAttributes: ['name' => 'Alpha']);
    [, $blog2] = createBlogWithOwner(blogAttributes: ['name' => 'Beta']);

    $userAlice = User::factory()->create(['name' => 'Alice']);
    $userBob = User::factory()->create(['name' => 'Bob']);

    $postBlog1 = Post::factory()->create(['blog_id' => $blog1->id]);
    $postBlog2 = Post::factory()->create(['blog_id' => $blog2->id]);

    $blogMorph = $blog1->getMorphClass();
    $postMorph = $postBlog1->getMorphClass();

    // Visitor 1 (Alice):
    // - 2 blog views for blog1
    // - 3 post views for blog1's post
    for ($i = 0; $i < 2; $i++) {
        createPageView($blogMorph, $blog1->id, ['user_id' => $userAlice->id]);
    }

    for ($i = 0; $i < 3; $i++) {
        createPageView($postMorph, $postBlog1->id, ['user_id' => $userAlice->id]);
    }

    // Visitor 2 (Bob): some views but mostly on blog2 so blog1 filter should exclude most
    createPageView($blogMorph, $blog2->id, ['user_id' => $userBob->id]);

    createPageView($postMorph, $postBlog2->id, ['user_id' => $userBob->id]);

    $service = new StatsService;
    $criteria = new StatsCriteria(
        range: StatsRange::Week,
        blogId: $blog1->id,
        limit: 10,
        sort: StatsSort::ViewsDesc,
    );

    $results = $service->visitorViews($criteria);

    expect($results)->toHaveCount(1);

    $row = $results[0];
    expect($row['visitor_label'])->toBe('Alice')
        ->and($row['blog_views'])->toBe(1) // distinct blog pages visited within range
        ->and($row['post_views'])->toBe(1); // distinct posts visited within range
});

it('aggregates visitor views by fingerprint', function () {
    Carbon::setTestNow('2025-01-15 12:00:00');

    [, $blog] = createBlogWithOwner();
    $blogMorph = $blog->getMorphClass();

    // Two different visitor_ids but same fingerprint
    createPageView($blogMorph, $blog->id, ['visitor_id' => 'v1', 'fingerprint' => 'f1']);
    createPageView($blogMorph, $blog->id, ['visitor_id' => 'v2', 'fingerprint' => 'f1']);

    // Another fingerprint
    createPageView($blogMorph, $blog->id, ['visitor_id' => 'v3', 'fingerprint' => 'f2']);

    $service = new StatsService;

    // Group by visitor_id (default)
    $criteria1 = new StatsCriteria(range: StatsRange::Week, visitorGroupBy: 'visitor_id');
    $results1 = $service->visitorViews($criteria1);
    expect($results1)->toHaveCount(3);

    // Group by fingerprint
    $criteria2 = new StatsCriteria(range: StatsRange::Week, visitorGroupBy: 'fingerprint');
    $results2 = $service->visitorViews($criteria2);
    expect($results2)->toHaveCount(2);

    $labels = $results2->pluck('visitor_label')->toArray();
    expect($labels)->toContain('f1')->toContain('f2');
});
