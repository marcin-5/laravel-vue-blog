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
        PageView::create(array_merge([
            'viewable_type' => $blogMorph,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess',
            'user_agent' => 'test',
        ], $data));
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
        PageView::create(array_merge([
            'viewable_type' => $postMorph,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess',
            'user_agent' => 'test',
        ], $data));
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
        // blog1 has two own post views plus one view of post2 from blog2 in our setup,
        // so aggregated post_views for blog1 is 4
        ->and($first['post_views'])->toBe(4)
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
        PageView::create(array_merge([
            'viewable_type' => $blogMorph,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess',
            'user_agent' => 'test',
        ], $data));
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
        PageView::create(array_merge([
            'viewable_type' => $blogMorph,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess',
            'user_agent' => 'test',
        ], $data));
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
        PageView::create(array_merge([
            'viewable_type' => $postMorph,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess',
            'user_agent' => 'test',
        ], $data));
    }

    // one old view outside of Week range
    $oldView = PageView::create([
        'viewable_type' => $postMorph,
        'viewable_id' => $post1->id,
        'ip_address' => '127.0.0.1',
        'session_id' => 'old',
        'user_agent' => 'test',
    ]);
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
        PageView::create([
            'viewable_type' => $postMorph,
            'viewable_id' => $post->id,
            'ip_address' => '127.0.0.1',
            'session_id' => 'sess',
            'user_agent' => 'test',
        ]);
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
