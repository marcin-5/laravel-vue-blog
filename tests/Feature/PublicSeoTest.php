<?php

use App\Models\Blog;
use App\Models\Post;

use function Pest\Laravel\get;

function assertSingleMetaDescription(string $html): void
{
    expect(substr_count($html, '<meta name="description"'))->toBe(1)
        ->and(str_contains($html, 'meta name="description" inertia'))->toBeFalse();
}

test('home page has single meta description without inertia', function () {
    $response = get(route('home'));

    $response->assertSuccessful();

    $html = $response->getContent();

    assertSingleMetaDescription($html);
});

test('about page has single meta description without inertia', function () {
    $response = get(route('about'));

    $response->assertSuccessful();

    $html = $response->getContent();

    assertSingleMetaDescription($html);
});

test('contact page has single meta description without inertia', function () {
    $response = get(route('contact'));

    $response->assertSuccessful();

    $html = $response->getContent();

    assertSingleMetaDescription($html);
});

test('public blog landing page has single meta description without inertia', function () {
    $blog = Blog::factory()->create([
        'is_published' => true,
    ]);

    $response = get(route('blog.public.landing', ['blog' => $blog->slug]));

    $response->assertSuccessful();

    $html = $response->getContent();

    assertSingleMetaDescription($html);
});

test('public blog post page has single meta description without inertia', function () {
    $blog = Blog::factory()->create([
        'is_published' => true,
    ]);

    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'visibility' => Post::VIS_PUBLIC,
        'is_published' => true,
    ]);

    $response = get(route('blog.public.post', ['blog' => $blog->slug, 'postSlug' => $post->slug]));

    $response->assertSuccessful();

    $html = $response->getContent();

    assertSingleMetaDescription($html);
});
