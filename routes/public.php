<?php

use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SecurityTxtController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureVisitorId;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleTranslations;
use App\Http\Middleware\TrackMarkdownRequests;
use App\Http\Middleware\UpdateVisitorOnLogin;
use App\Models\Blog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Spatie\MarkdownResponse\Middleware\ProvideMarkdownResponse;

$reservedSlugs = 'about|contact|newsletter|enneagram-test|admin|api|dashboard|settings|_|_debugbar|_telescope|robots|sitemap|sitemap\.xml|robots\.txt|login|register|logout|www|posts|blogs|groups|data|markdown';
$reservedRegex = '^(?!(' . $reservedSlugs . ')($|/)).+$';
$resolveBlog = static function (string $blogSlug): Blog {
    $blog = Blog::fromSlugAndHost($blogSlug, request()->getHost());
    abort_unless($blog !== null, 404);

    return $blog;
};
$redirectToBlog = static function (Blog $blog, string $path = ''): RedirectResponse {
    $mainDomain = Blog::mainDomainForHost(request()->getHost());
    $scheme = request()->isSecure() ? 'https://' : 'http://';

    return redirect()->to($scheme . $blog->slug . '.' . $mainDomain . $path, 301);
};

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
        Route::get('.well-known/security.txt', SecurityTxtController::class);
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
Route::get('blogs/{blog_slug}/{postSlug}', function (string $blog_slug, string $postSlug) use ($resolveBlog, $redirectToBlog) {
    return $redirectToBlog($resolveBlog($blog_slug), '/' . $postSlug);
});

Route::get('blogs/{blog_slug}', function (string $blog_slug) use ($resolveBlog, $redirectToBlog) {
    return $redirectToBlog($resolveBlog($blog_slug));
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
Route::get('{blog_slug}/tags/{tagSlug}', function (string $blog_slug, string $tagSlug) use ($resolveBlog, $redirectToBlog) {
    $blog = $resolveBlog($blog_slug);
    $tag = $blog->tags()->where('slug', $tagSlug)->firstOrFail();

    return $redirectToBlog($blog, '/tags/' . $tag->slug);
})->where('blog_slug', $reservedRegex);

Route::get('{blog_slug}/{postSlug}', function (string $blog_slug, string $postSlug) use ($resolveBlog, $redirectToBlog) {
    return $redirectToBlog($resolveBlog($blog_slug), '/' . $postSlug);
})->where('blog_slug', $reservedRegex);

Route::get('{blog_slug}', function (string $blog_slug) use ($resolveBlog, $redirectToBlog) {
    return $redirectToBlog($resolveBlog($blog_slug));
})->where('blog_slug', $reservedRegex);

Route::get('/', [PublicHomeController::class, 'welcome'])->name('home');
