<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createPublicBlogWithPosts(array $attributes = []): Blog
{
    $owner = User::factory()->create();

    /** @var Blog $blog */
    $blog = Blog::factory()->for($owner)->create(array_merge([
        'is_published' => true,
        'locale' => 'pl',
        'sidebar' => -30,
        'footer' => 'Footer **content**',
    ], $attributes));

    Post::factory()->count(2)->for($blog)->create([
        'is_published' => true,
        'visibility' => Post::VIS_PUBLIC,
        'published_at' => now()->subDay(),
    ]);

    return $blog;
}

it('exposes grouped chrome and listing props on the landing page', function () {
    $blog = createPublicBlogWithPosts();

    $this
        ->get(getBlogUrl($blog))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('public/blog/Landing')
            ->has('blog')
            ->has('landingHtml')
            ->where('chrome.locale', 'pl')
            ->where('chrome.sidebar', -30)
            ->where('chrome.sidebarPosition', 'left')
            ->has('chrome.navigation')
            ->has('chrome.footerHtml')
            ->has('listing.posts', 2)
            ->has('listing.pagination')
            ->has('listing.allTags')
            ->where('listing.activeTag', null)
            ->has('seo')
            ->has('translations.messages')
            ->where('translations.locale', 'pl')
            ->etc(),
        );
});

it('exposes grouped chrome and listing props alongside the post on the post page', function () {
    $blog = createPublicBlogWithPosts();
    $post = $blog->posts()->first();

    $this
        ->get(getBlogUrl($blog, "/$post->slug"))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('public/blog/Post')
            ->has('blog')
            ->has('post')
            ->has('seo')
            ->where('chrome.locale', 'pl')
            ->where('chrome.sidebar', -30)
            ->has('chrome.navigation')
            ->has('listing.posts', 2)
            ->has('listing.pagination')
            ->has('listing.allTags')
            ->has('translations.messages')
            ->where('translations.locale', 'pl')
            ->etc(),
        );
});

it('exposes the active tag and the filtered posts on the tag page', function () {
    $blog = createPublicBlogWithPosts();

    /** @var Tag $tag */
    $tag = Tag::factory()->for($blog)->create(['name' => 'Laravel']);
    $blog->posts()->first()->tags()->attach($tag->id);

    $this
        ->get(getBlogUrl($blog, "/tags/$tag->slug"))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('public/blog/Landing')
            ->has('blog')
            ->has('landingHtml')
            ->has('chrome.navigation')
            ->where('listing.activeTag.slug', $tag->slug)
            ->has('listing.posts', 1)
            ->has('seo')
            ->has('translations.messages')
            ->missing('viewStats')
            ->where('translations.locale', 'pl')
            ->loadDeferredProps(fn(Assert $reload) => $reload
                ->has('viewStats'),
            )
            ->etc(),
        );
});

it('exposes an empty posts listing for a tag without posts', function () {
    $blog = createPublicBlogWithPosts();

    /** @var Tag $tag */
    $tag = Tag::factory()->for($blog)->create(['name' => 'Empty']);

    $this
        ->get(getBlogUrl($blog, "/tags/$tag->slug"))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->has('listing.posts', 0)
            ->where('listing.activeTag.slug', $tag->slug)
            ->has('listing.pagination')
            ->etc(),
        );
});

it('exposes the footer html through chrome on the about and contact pages', function (string $path, string $component) {
    $blog = createPublicBlogWithPosts();

    $this
        ->get(getBlogUrl($blog, $path))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component($component)
            ->has('blog')
            ->has('chrome.footerHtml')
            ->has('seo')
            ->has('translations.messages')
            ->where('chrome.locale', 'pl')
            ->where('translations.locale', 'pl')
            ->etc(),
        );
})->with([
    'about' => ['/about', 'public/blog/About'],
    'contact' => ['/contact', 'public/blog/Contact'],
]);

it('exposes a displayed motto picked from the configured mottos', function () {
    $blog = createPublicBlogWithPosts(['motto' => "First motto\n\nSecond motto"]);

    $this
        ->get(getBlogUrl($blog))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('blog.displayedMotto', fn(?string $motto) => in_array($motto, ['First motto', 'Second motto'], true),
            )
            ->etc(),
        );
});

it('exposes a null displayed motto when the blog has no motto', function () {
    $blog = createPublicBlogWithPosts(['motto' => null]);

    $this
        ->get(getBlogUrl($blog))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('blog.displayedMotto', null)
            ->etc(),
        );
});
