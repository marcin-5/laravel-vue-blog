<?php

use App\Models\Post;

use function Pest\Laravel\get;

function assertSingleMetaDescription(string $html): void
{
    preg_match_all('/<meta\b(?=[^>]*\bname=["\']description["\'])[^>]*>/i', $html, $matches);

    expect($matches[0])->toHaveCount(1);
}

test('home page has a single meta description', function () {
    $response = get(route('home'));

    $response->assertSuccessful();

    $html = $response->getContent();

    assertSingleMetaDescription($html);
});

test('about page has a single meta description', function () {
    $response = get(route('about'));

    $response->assertSuccessful();

    $html = $response->getContent();

    // dd($html);

    assertSingleMetaDescription($html);
});

test('contact page has a single meta description', function () {
    $response = get(route('contact'));

    $response->assertSuccessful();

    $html = $response->getContent();

    // dd($html);

    assertSingleMetaDescription($html);
});

test('public blog landing page has a single meta description', function () {
    $blog = createBlog([
        'is_published' => true,
    ]);

    $response = get(getBlogUrl($blog));

    $response->assertSuccessful();

    $html = $response->getContent();

    // dd($html);

    assertSingleMetaDescription($html);
});

test('public blog post page has a single meta description', function () {
    $blog = createBlog([
        'is_published' => true,
    ]);

    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'visibility' => Post::VIS_PUBLIC,
        'is_published' => true,
    ]);

    $response = get(getBlogUrl($blog, "/{$post->slug}"));

    $response->assertSuccessful();

    $html = $response->getContent();

    // dd($html);

    assertSingleMetaDescription($html);
});
