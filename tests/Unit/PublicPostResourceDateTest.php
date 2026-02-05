<?php

use App\Http\Resources\PublicPostResource;
use App\Models\Post;
use Illuminate\Support\Carbon;

test('public post resource returns published_at in Y-m-d format regardless of locale', function () {
    $post = Post::factory()->create([
        'published_at' => Carbon::parse('2026-02-05 10:00:00'),
    ]);

    $resource = new PublicPostResource($post);
    $array = $resource->toArray(request());

    expect($array['published_at'])->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    expect(new DateTime($array['published_at']))->format('Y-m-d')->toBe('2026-02-05');
});
