<?php

use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureVisitorId;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleTranslations;
use App\Http\Middleware\NoIndexMiddleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackPageViews;
use App\Http\Middleware\UpdateVisitorOnLogin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            SetLocale::class,
            HandleTranslations::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ContentSecurityPolicy::class,
            EnsureVisitorId::class,
            UpdateVisitorOnLogin::class,
        ]);

        $middleware->alias([
            'track-page-views' => TrackPageViews::class,
            'noindex' => NoIndexMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
