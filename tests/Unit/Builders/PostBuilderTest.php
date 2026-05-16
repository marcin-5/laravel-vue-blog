<?php

declare(strict_types=1);

use App\Models\Post;

it('filters extension posts', function () {
    Post::factory()->create(['visibility' => Post::VIS_EXTENSION]);
    Post::factory()->create(['visibility' => Post::VIS_PUBLIC]);

    expect(Post::query()->extensionType()->count())->toBe(1);
});

it('filters regular posts', function () {
    Post::factory()->create(['visibility' => Post::VIS_EXTENSION]);
    Post::factory()->create(['visibility' => Post::VIS_PUBLIC]);

    expect(Post::query()->regularPosts()->count())->toBe(1);
});

it('filters published posts', function () {
    Post::factory()->create(['published_at' => now()->subDay(), 'is_published' => true]);
    Post::factory()->create(['published_at' => now()->addDay(), 'is_published' => true]);
    Post::factory()->create(['published_at' => null, 'is_published' => false]);

    expect(Post::query()->published()->count())->toBe(1);
});

it('filters public posts', function () {
    Post::factory()->create(['visibility' => Post::VIS_PUBLIC]);
    Post::factory()->create(['visibility' => Post::VIS_REGISTERED]);

    expect(Post::query()->public()->count())->toBe(1);
});

it('orders by publication date', function () {
    $p1 = Post::factory()->create(['published_at' => now()->subDays(2), 'created_at' => now()->subDays(2)]);
    $p2 = Post::factory()->create(['published_at' => now()->subDay(), 'created_at' => now()->subDay()]);

    $results = Post::query()->orderByPublicationDate('desc')->get();

    expect($results->first()->id)->toBe($p2->id);
});
