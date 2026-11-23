<?php

use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use App\Queries\Dashboard\SeoTitleIssuesQuery;

test('it reports rendered short blog and post titles for only the given user', function () {
    $user = User::factory()->create(['role' => UserRole::Blogger->value]);
    $otherUser = User::factory()->create(['role' => UserRole::Blogger->value]);
    $blog = Blog::factory()->for($user)->create([
        'name' => 'Blog Name Long Enough',
        'seo_title' => 'Short blog',
    ]);
    $post = Post::factory()->for($blog)->create([
        'title' => 'A longer post title',
        'seo_title' => 'Short post',
    ]);
    $otherBlog = Blog::factory()->for($otherUser)->create([
        'seo_title' => 'Other short',
    ]);
    Post::factory()->for($otherBlog)->create(['seo_title' => 'Other post']);

    $issues = app(SeoTitleIssuesQuery::class)->handle($user);

    expect($issues)->toHaveCount(2)
        ->and($issues->pluck('type')->all())->toBe(['blog', 'post'])
        ->and($issues->pluck('title')->all())->toBe(['Short blog', 'Short post'])
        ->and($issues->pluck('length')->all())->toBe([10, 10])
        ->and($issues->pluck('blog_id')->all())->toBe([$blog->id, $blog->id])
        ->and($issues->pluck('post_id')->all())->toBe([null, $post->id]);
});

test('it measures fallback titles and excludes titles of fifteen characters or more', function () {
    config(['app.name' => 'Site']);
    $user = User::factory()->create(['role' => UserRole::Blogger->value]);
    $blog = Blog::factory()->for($user)->create([
        'name' => 'AB',
        'seo_title' => null,
    ]);
    $fallbackPost = Post::factory()->for($blog)->create([
        'title' => 'Hi',
        'seo_title' => null,
    ]);
    $boundaryPost = Post::factory()->for($blog)->create([
        'title' => 'Title not relevant',
        'seo_title' => '123456789012345',
    ]);

    $issues = app(SeoTitleIssuesQuery::class)->handle($user);

    expect($issues)->toHaveCount(2)
        ->and($issues->pluck('title')->all())->toBe(['AB - Site', 'Hi - AB'])
        ->and($issues->pluck('length')->all())->toBe([9, 7])
        ->and($issues->pluck('post_id')->all())->toBe([null, $fallbackPost->id])
        ->and($issues->pluck('post_id'))->not->toContain($boundaryPost->id);
});

test('it counts decoded html entities and trims rendered titles before measuring', function () {
    $user = User::factory()->create(['role' => UserRole::Blogger->value]);
    $blog = Blog::factory()->for($user)->create([
        'name' => 'Blog Name',
        'seo_title' => '  123456789012 &amp;  ',
    ]);

    $issues = app(SeoTitleIssuesQuery::class)->handle($user);

    expect($issues)->toHaveCount(1)
        ->and($issues->first()['title'])->toBe('123456789012 &')
        ->and($issues->first()['length'])->toBe(14);
});

test('it excludes fallback titles extended to fifteen characters by the site name', function () {
    $user = User::factory()->create(['role' => UserRole::Blogger->value]);
    Blog::factory()->for($user)->create([
        'name' => 'A blog name long enough',
        'seo_title' => null,
    ]);

    expect(app(SeoTitleIssuesQuery::class)->handle($user))->toBeEmpty();
});
