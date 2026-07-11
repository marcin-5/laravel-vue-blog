<?php

use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureVisitorId;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleTranslations;
use App\Http\Middleware\TrackMarkdownRequests;
use App\Http\Middleware\UpdateVisitorOnLogin;
use App\Models\Blog;
use App\Models\Tag;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Spatie\MarkdownResponse\Middleware\ProvideMarkdownResponse;

$reservedSlugs = 'about|contact|newsletter|enneagram-test|admin|api|dashboard|settings|_|_debugbar|_telescope|robots|sitemap|sitemap\.xml|robots\.txt|login|register|logout|www|posts|blogs|groups|data|markdown';
$reservedRegex = '^(?!(' . $reservedSlugs . ')($|/)).+$';

// Robots.txt and Sitemap routes (without Inertia and appearance middleware)
Route::withoutMiddleware([
    HandleInertiaRequests::class,
    AddLinkHeadersForPreloadedAssets::class,
    HandleAppearance::class,
    HandleTranslations::class,
    ContentSecurityPolicy::class,
    EnsureVisitorId::class,
    UpdateVisitorOnLogin::class,
])
    ->group(function () {
        Route::get('robots.txt', [RobotsController::class, 'generate']);
        Route::get('sitemap.xml', [SitemapController::class, 'generate'])->name('sitemap');
    });

// Subdomain blog routes (must be registered BEFORE the generic '/' route)
Route::domain('{blog:slug}.{mainDomain}')
    // Ensure we only match real subdomains (e.g., blog.osobliwy.localhost), not main domains
    ->where(['mainDomain' => '.+\..+'])
    ->group(function () use ($reservedRegex) {
        Route::get('/', [PublicBlogController::class, 'landing'])
            ->name('blog.public.landing')
            ->middleware(['track-page-views', TrackMarkdownRequests::class, ProvideMarkdownResponse::class]);

        Route::get('/about', [PublicBlogController::class, 'about'])
            ->name('blog.public.about')
            ->middleware(['track-page-views', TrackMarkdownRequests::class, ProvideMarkdownResponse::class]);

        Route::get('/contact', [PublicBlogController::class, 'contact'])
            ->name('blog.public.contact');
        Route::post('/contact', [PublicBlogController::class, 'submitContact'])
            ->name('blog.public.contact.submit')
            ->middleware(['throttle:6,1']);

        Route::get('/tags/{tag:slug}', [PublicBlogController::class, 'tag'])
            ->name('blog.public.tag');

        Route::get('/{postSlug}', [PublicBlogController::class, 'post'])
            ->name('blog.public.post')
            ->where('postSlug', $reservedRegex)
            ->middleware(['track-page-views', TrackMarkdownRequests::class, ProvideMarkdownResponse::class]);
    });

// Redirect robots to the subdomain
Route::get('blogs/{blog_slug}/{postSlug}', function (string $blog_slug, string $postSlug) {
    $mainDomains = [config('app.domain'), config('app.domain_secondary')];
    $blog = Blog::withoutGlobalScopes()->where('slug', $blog_slug)->firstOrFail();
    $host = request()->getHost();
    $mainDomain = collect($mainDomains)->first(fn($d) => str_ends_with($host, $d)) ?? $mainDomains[0];

    return redirect()->to(
        (request()->isSecure() ? 'https://' : 'http://') . $blog->slug . '.' . $mainDomain . '/' . $postSlug,
        301,
    );
});

Route::get('blogs/{blog_slug}', function (string $blog_slug) {
    $mainDomains = [config('app.domain'), config('app.domain_secondary')];
    $blog = Blog::withoutGlobalScopes()->where('slug', $blog_slug)->firstOrFail();
    $host = request()->getHost();
    $mainDomain = collect($mainDomains)->first(fn($d) => str_ends_with($host, $d)) ?? $mainDomains[0];

    return redirect()->to((request()->isSecure() ? 'https://' : 'http://') . $blog->slug . '.' . $mainDomain, 301);
});

// Public About page (SSR): provide translations via props
Route::get('/about', [PublicHomeController::class, 'about'])->name('about');

// Public Contact page (SSR)
Route::get('/contact', [PublicHomeController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicHomeController::class, 'submit'])
    ->name('public.contact.submit')
    ->middleware(['throttle:6,1']); // rate-limit to reduce spam

// Newsletter
Route::get('/newsletter', [NewsletterController::class, 'index'])->name('newsletter.index');
Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store');
Route::get('/newsletter/manage', [NewsletterController::class, 'manage'])->name('newsletter.manage');
Route::post('/newsletter/update', [NewsletterController::class, 'update'])->name('newsletter.update');
Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Redirects for old URL structure on main domains
Route::get('{blog_slug}/tags/{tag:slug}', function (string $blog_slug, Tag $tag) {
    $mainDomains = [config('app.domain'), config('app.domain_secondary')];
    $blog = Blog::withoutGlobalScopes()->where('slug', $blog_slug)->firstOrFail();
    $host = request()->getHost();
    $mainDomain = collect($mainDomains)->first(fn($d) => str_ends_with($host, $d)) ?? $mainDomains[0];

    return redirect()->to(
        (request()->isSecure() ? 'https://' : 'http://') . $blog->slug . '.' . $mainDomain . '/tags/' . $tag->slug,
        301,
    );
})->where('blog_slug', $reservedRegex);

Route::get('{blog_slug}/{postSlug}', function (string $blog_slug, string $postSlug) {
    $mainDomains = [config('app.domain'), config('app.domain_secondary')];
    $blog = Blog::withoutGlobalScopes()->where('slug', $blog_slug)->firstOrFail();
    $host = request()->getHost();
    $mainDomain = collect($mainDomains)->first(fn($d) => str_ends_with($host, $d)) ?? $mainDomains[0];

    return redirect()->to(
        (request()->isSecure() ? 'https://' : 'http://') . $blog->slug . '.' . $mainDomain . '/' . $postSlug,
        301,
    );
})->where('blog_slug', $reservedRegex);

Route::get('{blog_slug}', function (string $blog_slug) {
    $mainDomains = [config('app.domain'), config('app.domain_secondary')];
    $blog = Blog::withoutGlobalScopes()->where('slug', $blog_slug)->firstOrFail();
    $host = request()->getHost();
    $mainDomain = collect($mainDomains)->first(fn($d) => str_ends_with($host, $d)) ?? $mainDomains[0];

    return redirect()->to((request()->isSecure() ? 'https://' : 'http://') . $blog->slug . '.' . $mainDomain, 301);
})->where('blog_slug', $reservedRegex);

Route::get('/', [PublicHomeController::class, 'welcome'])->name('home');
