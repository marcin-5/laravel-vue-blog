<?php

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

// Keep these at the very end to avoid conflicts.
Route::get('{blog:slug}/{postSlug}', [PublicBlogController::class, 'post'])
    ->name('blog.public.post');

Route::get('{blog:slug}', [PublicBlogController::class, 'landing'])
    ->name('blog.public.landing');

Route::get('/', [PublicHomeController::class, 'welcome'])->name('home');
