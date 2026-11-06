<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lists only posts assigned to the given tag within the blog', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);

    $posts = Post::factory()->count(3)->for($blog)->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);

    /** @var Tag $tag */
    $tag = Tag::factory()->for($blog)->create(['name' => 'Laravel']);

    // Attach tag to first two posts
    $posts[0]->tags()->attach($tag->id);
    $posts[1]->tags()->attach($tag->id);

    $response = $this->get(getBlogUrl($blog, "/tags/$tag->slug"));

    $response->assertOk();
    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/blog/Landing')
        ->has('listing.activeTag', fn(Assert $a) => $a
            ->where('name', 'Laravel')
            ->etc(),
        )
        ->has('listing.posts', 2)
        ->has('listing.pagination')
        ->where('seo.canonicalUrl', getBlogUrl($blog, "/tags/$tag->slug")),
    );
});

it('returns 404 when tag does not belong to the blog', function () {
    $owner = User::factory()->create();
    $blogA = Blog::factory()->for($owner)->create(['is_published' => true]);
    $blogB = Blog::factory()->for($owner)->create(['is_published' => true]);

    $tagB = Tag::factory()->for($blogB)->create();

    $this
        ->get(getBlogUrl($blogA, "/tags/$tagB->slug"))
        ->assertNotFound();
});

it('ignores query string tag lists when multiple tag filtering is disabled', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $posts = Post::factory()->count(2)->for($blog)->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);
    $tag = Tag::factory()->for($blog)->create();
    $posts[0]->tags()->attach($tag->id);

    $this->get(getBlogUrl($blog, '/?' . http_build_query(['tags' => [$tag->slug]])))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->has('listing.posts', 2)
            ->has('listing.activeTags', 0)
            ->etc(),
        );

    $this->get(getBlogUrl($blog, '/?tag=' . $tag->slug))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->has('listing.posts', 2)
            ->has('listing.activeTags', 0)
            ->etc(),
        );
});

it('filters multiple tags using the selected operator', function (string $operator, int $expectedCount) {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'allow_multiple_tag_filtering' => true,
    ]);
    $posts = Post::factory()->count(3)->for($blog)->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);
    $tagA = Tag::factory()->for($blog)->create(['name' => 'Tag A']);
    $tagB = Tag::factory()->for($blog)->create(['name' => 'Tag B']);
    $posts[0]->tags()->attach([$tagA->id, $tagB->id]);
    $posts[1]->tags()->attach($tagA->id);
    $posts[2]->tags()->attach($tagB->id);

    $query = http_build_query([
        'tags' => [$tagA->slug, $tagB->slug],
        'operator' => $operator,
    ]);

    $this->get(getBlogUrl($blog, '/?' . $query))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->where('listing.operator', $operator)
            ->has('listing.activeTags', 2)
            ->has('listing.posts', $expectedCount)
            ->where('chrome.navigation.landingUrl', fn(string $url) => str_contains($url, 'tags%5B0%5D=' . $tagA->slug)
                && str_contains($url, 'tags%5B1%5D=' . $tagB->slug)
                && str_contains($url, 'operator=' . $operator))
            ->etc(),
        );
})->with([
    'and' => ['AND', 1],
    'or' => ['OR', 3],
]);

it('uses AND when multiple tags are selected without an operator', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'allow_multiple_tag_filtering' => true,
    ]);
    $posts = Post::factory()->count(2)->for($blog)->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);
    $tagA = Tag::factory()->for($blog)->create(['name' => 'Tag A']);
    $tagB = Tag::factory()->for($blog)->create(['name' => 'Tag B']);
    $posts[0]->tags()->attach([$tagA->id, $tagB->id]);
    $posts[1]->tags()->attach($tagA->id);

    $query = http_build_query(['tags' => [$tagA->slug, $tagB->slug]]);

    $this->get(getBlogUrl($blog, '/?' . $query))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->where('listing.operator', 'AND')
            ->has('listing.posts', 1)
            ->etc(),
        );
});

it('ignores tags from another blog in multi-tag filters', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'allow_multiple_tag_filtering' => true,
    ]);
    $otherBlog = Blog::factory()->for($owner)->create(['is_published' => true]);
    $post = Post::factory()->for($blog)->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);
    $tag = Tag::factory()->for($otherBlog)->create();

    $this->get(getBlogUrl($blog, '/?' . http_build_query(['tags' => [$tag->slug]])))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->has('listing.posts', 1)
            ->has('listing.activeTags', 0)
            ->etc(),
        );
});

it('returns no posts when no post matches all selected tags', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'allow_multiple_tag_filtering' => true,
    ]);
    $post = Post::factory()->for($blog)->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);
    $tagA = Tag::factory()->for($blog)->create(['name' => 'Tag A']);
    $tagB = Tag::factory()->for($blog)->create(['name' => 'Tag B']);
    $post->tags()->attach($tagA->id);

    $this->get(getBlogUrl($blog, '/?' . http_build_query(['tags' => [$tagA->slug, $tagB->slug]])))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->has('listing.posts', 0)
            ->where('listing.pagination.total', 0)
            ->etc(),
        );
});

it('preserves multiple tag context in post navigation', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'allow_multiple_tag_filtering' => true,
    ]);
    $tagA = Tag::factory()->for($blog)->create(['name' => 'Tag A']);
    $tagB = Tag::factory()->for($blog)->create(['name' => 'Tag B']);
    $posts = Post::factory()->count(2)->for($blog)->sequence(
        ['published_at' => now()->subDays(2)],
        ['published_at' => now()->subDay()],
    )->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
    ]);
    foreach ($posts as $post) {
        $post->tags()->attach([$tagA->id, $tagB->id]);
    }

    $query = http_build_query([
        'tags' => [$tagA->slug, $tagB->slug],
        'operator' => 'OR',
    ]);

    $this->get(getBlogUrl($blog, "/{$posts[1]->slug}?{$query}"))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->where('listing.operator', 'OR')
            ->where('chrome.navigation.landingUrl', fn(string $url) => str_contains($url, 'operator=OR'))
            ->etc(),
        );
});

it('preserves multiple tag filters and the operator in pagination links', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'is_published' => true,
        'allow_multiple_tag_filtering' => true,
        'page_size' => 1,
    ]);
    $tagA = Tag::factory()->for($blog)->create(['name' => 'Tag A']);
    $tagB = Tag::factory()->for($blog)->create(['name' => 'Tag B']);
    $posts = Post::factory()->count(2)->for($blog)->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);
    foreach ($posts as $post) {
        $post->tags()->attach([$tagA->id, $tagB->id]);
    }

    $query = http_build_query([
        'tags' => [$tagA->slug, $tagB->slug],
        'operator' => 'OR',
    ]);

    $this->get(getBlogUrl($blog, '/?' . $query))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->where('listing.pagination.nextUrl', fn(string $url) => str_contains($url, 'operator=OR')
                && str_contains($url, 'tags%5B0%5D=' . $tagA->slug)
                && str_contains($url, 'tags%5B1%5D=' . $tagB->slug))
            ->etc(),
        );
});
