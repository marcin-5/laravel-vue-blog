<?php

use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

// Robots.txt and Sitemap routes (without Inertia and appearance middleware)
Route::withoutMiddleware([
    HandleInertiaRequests::class,
    AddLinkHeadersForPreloadedAssets::class,
    HandleAppearance::class,
    SetLocale::class,
])
    ->group(function () {
        Route::get('robots.txt', [RobotsController::class, 'generate']);
        Route::get('sitemap.xml', [SitemapController::class, 'generate'])->name('sitemap');
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

// Keep these at the very end to avoid conflicts.
Route::get('{blog:slug}/{postSlug}', [PublicBlogController::class, 'post'])
    ->name('blog.public.post')
    ->middleware('track-page-views');

Route::get('{blog:slug}', [PublicBlogController::class, 'landing'])
    ->name('blog.public.landing')
    ->middleware('track-page-views');

Route::get('/', [PublicHomeController::class, 'welcome'])->name('home');
