<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\RecommendedLink;
use App\Models\RecommendedLinkCategory;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Config::set('app.domain', 'osobliwy.pl');
    Config::set('app.domain_secondary', 'osobliwy.com');
});

it('renders published recommended links with synchronous props and SEO in the initial response', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create([
        'name' => 'Recommended Blog',
        'slug' => 'recommended-blog',
        'is_published' => true,
        'locale' => 'en',
    ]);
    RecommendedLink::factory()->for($blog)->create([
        'url' => 'https://example.com/resources',
        'name' => 'Example resources',
        'description' => 'A useful collection.',
    ]);

    $response = $this->get('http://recommended-blog.osobliwy.com/recommended');

    $response->assertOk()
        ->assertInertia(fn($page) => $page
            ->component('public/blog/Recommended')
            ->where('recommendedLinks.0.url', 'https://example.com/resources')
            ->where('recommendedLinks.0.name', 'Example resources')
            ->where('recommendedLinks.0.description', 'A useful collection.')
            ->where('chrome.navigation.recommendedLinksUrl', 'http://recommended-blog.osobliwy.com/recommended')
            ->where('seo.title', 'Recommended websites - Recommended Blog')
            ->where('seo.description', 'Explore websites recommended by Recommended Blog.')
            ->where('seo.canonicalUrl', 'http://recommended-blog.osobliwy.com/recommended'));

    expect($response->getContent())
        ->toContain('Example resources')
        ->toContain('https://example.com/resources')
        ->toContain('A useful collection.')
        ->toContain('<title')
        ->toContain('Recommended websites - Recommended Blog')
        ->toContain('name="description"')
        ->toContain('Explore websites recommended by Recommended Blog.');
});

it('omits the navigation link and empty message when a blog has no recommended links', function () {
    $blog = Blog::factory()->for(User::factory())->create([
        'slug' => 'no-recommended-links',
        'is_published' => true,
        'locale' => 'en',
    ]);

    $response = $this->get('http://no-recommended-links.osobliwy.com/recommended');

    $response->assertOk()
        ->assertInertia(fn($page) => $page
            ->where('recommendedLinks', [])
            ->where('chrome.navigation.recommendedLinksUrl', null));

    expect($response->getContent())->not->toContain('<p class="text-muted-foreground">No recommended websites yet.</p>');
});

it('uses the URL when a recommended link has no name and omits an empty description', function () {
    $blog = Blog::factory()->for(User::factory())->create([
        'slug' => 'recommended-fallback',
        'is_published' => true,
        'locale' => 'pl',
    ]);
    RecommendedLink::factory()->for($blog)->create([
        'url' => 'https://example.net/',
        'name' => null,
        'description' => null,
    ]);

    $response = $this->get('http://recommended-fallback.osobliwy.pl/recommended');

    $response->assertOk()
        ->assertInertia(fn($page) => $page
            ->component('public/blog/Recommended')
            ->where('recommendedLinks.0.name', null)
            ->where('recommendedLinks.0.description', null)
            ->where('seo.title', 'Polecane strony - ' . $blog->name));

    expect($response->getContent())->toContain('https://example.net/');
});

it('includes the recommended links URL in a post page navigation payload', function () {
    $blog = Blog::factory()->for(User::factory())->create([
        'slug' => 'post-navigation-recommendations',
        'is_published' => true,
        'locale' => 'en',
    ]);
    Post::factory()->for($blog)->create([
        'slug' => 'navigation-test-post',
        'published_at' => now(),
    ]);
    RecommendedLink::factory()->for($blog)->create();

    $this->get('http://post-navigation-recommendations.osobliwy.com/navigation-test-post')
        ->assertOk()
        ->assertInertia(fn($page) => $page
            ->where('chrome.navigation.recommendedLinksUrl', 'http://post-navigation-recommendations.osobliwy.com/recommended'));
});

it('returns not found for recommended links on an unpublished blog', function () {
    $blog = Blog::factory()->for(User::factory())->create([
        'slug' => 'unpublished-recommendations',
        'is_published' => false,
        'locale' => 'pl',
    ]);

    $this->get('http://unpublished-recommendations.osobliwy.pl/recommended')->assertNotFound();
});

it('escapes recommended link names and descriptions in the rendered page', function () {
    $blog = Blog::factory()->for(User::factory())->create([
        'slug' => 'escaped-recommendations',
        'is_published' => true,
        'locale' => 'en',
    ]);
    RecommendedLink::factory()->for($blog)->create([
        'url' => 'https://example.com/',
        'name' => '<script>alert(1)</script>',
        'description' => '<script>alert(2)</script>',
    ]);

    $response = $this->get('http://escaped-recommendations.osobliwy.com/recommended');

    $response->assertOk();
    expect($response->getContent())
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>alert(1)</script>')
        ->not->toContain('<script>alert(2)</script>');
});

it('groups public links by category order and orders links within each section', function () {
    $blog = Blog::factory()->for(User::factory())->create([
        'slug' => 'ordered-recommendations',
        'is_published' => true,
        'locale' => 'en',
    ]);
    $firstCategory = RecommendedLinkCategory::factory()->for($blog)->create([
        'name' => 'Culture',
        'display_order' => 1,
    ]);
    $secondCategory = RecommendedLinkCategory::factory()->for($blog)->create([
        'name' => 'Science',
        'display_order' => 0,
    ]);
    RecommendedLink::factory()->for($blog)->create([
        'url' => 'https://example.com/uncategorized-last',
        'display_order' => 1,
    ]);
    RecommendedLink::factory()->for($blog)->create([
        'url' => 'https://example.com/uncategorized-first',
        'display_order' => 0,
    ]);
    RecommendedLink::factory()->for($blog)->create([
        'category_id' => $firstCategory->id,
        'url' => 'https://example.com/culture-last',
        'display_order' => 1,
    ]);
    RecommendedLink::factory()->for($blog)->create([
        'category_id' => $firstCategory->id,
        'url' => 'https://example.com/culture-first',
        'display_order' => 0,
    ]);
    RecommendedLink::factory()->for($blog)->create([
        'category_id' => $secondCategory->id,
        'url' => 'https://example.com/science',
        'display_order' => 0,
    ]);

    $this->get('http://ordered-recommendations.osobliwy.com/recommended')
        ->assertOk()
        ->assertInertia(fn($page) => $page
            ->where('recommendedLinks.0.url', 'https://example.com/uncategorized-first')
            ->where('recommendedLinks.1.url', 'https://example.com/uncategorized-last')
            ->where('recommendedLinkCategories.0.name', 'Science')
            ->where('recommendedLinkCategories.0.recommendedLinks.0.url', 'https://example.com/science')
            ->where('recommendedLinkCategories.1.name', 'Culture')
            ->where('recommendedLinkCategories.1.recommendedLinks.0.url', 'https://example.com/culture-first')
            ->where('recommendedLinkCategories.1.recommendedLinks.1.url', 'https://example.com/culture-last'));
});

it('eager loads public recommended links with a fixed number of relationship queries', function () {
    $blog = Blog::factory()->for(User::factory())->create([
        'slug' => 'eager-loaded-recommendations',
        'is_published' => true,
        'locale' => 'en',
    ]);

    foreach (range(1, 4) as $order) {
        $category = RecommendedLinkCategory::factory()->for($blog)->create(['display_order' => $order]);
        RecommendedLink::factory()->for($blog)->create([
            'category_id' => $category->id,
            'display_order' => 0,
        ]);
    }

    Cache::flush();
    $recommendedLinkQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$recommendedLinkQueries): void {
        if (str_contains($query->sql, 'recommended_links')) {
            $recommendedLinkQueries++;
        }
    });

    $this->get('http://eager-loaded-recommendations.osobliwy.com/recommended')->assertOk();

    expect($recommendedLinkQueries)->toBe(3);
});
