<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'pl'], true), 404);

    $jsonPath = resource_path("lang/{$locale}.json");
    $messages = File::exists($jsonPath)
        ? json_decode(File::get($jsonPath), true)
        : [];

    return response()->json([
        'locale' => $locale,
        'messages' => $messages,
    ]);
})->name('lang.messages');

// Per-namespace bundles: /lang/{locale}/{namespace}
Route::get('/lang/{locale}/{namespace}', function (string $locale, string $namespace) {
    abort_unless(in_array($locale, ['en', 'pl'], true), 404);

    // Whitelist allowed namespaces to avoid arbitrary file reads
    $allowed = [
        'about',
        'admin',
        'app',
        'appearance',
        'auth',
        'blog',
        'blogger',
        'common',
        'contact',
        'dashboard',
        'newsletter',
        'password',
        'profile',
        'welcome'
    ];
    abort_unless(in_array($namespace, $allowed, true), 404);

    $jsonPath = resource_path("lang/{$locale}/{$namespace}.json");
    $messages = File::exists($jsonPath)
        ? json_decode(File::get($jsonPath), true)
        : [];

    return response()->json([
        'locale' => $locale,
        'namespace' => $namespace,
        'messages' => $messages,
    ]);
})->name('lang.namespace');

Route::post('/locale', function (Request $request) {
    $data = $request->validate([
        'locale' => ['required', 'string', 'in:en,pl'],
    ]);

    $locale = $data['locale'];
    App::setLocale($locale);

    if ($user = $request->user()) {
        $user->update(['locale' => $locale]);
    }

    $response = back();
    return $response->withCookie(cookie('locale', $locale, 60 * 24 * 365));
})->middleware('auth');
