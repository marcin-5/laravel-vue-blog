<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return Inertia::render('Welcome');
//})->name('home');

Route::middleware(['auth', 'verified', 'noindex'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('_')->group(function () {
        Route::get('/{group:slug}', [GroupController::class, 'landing'])->name('group.landing');
        Route::get('/{group:slug}/{postSlug}', [GroupController::class, 'post'])->name('group.post');
    });
});

// Grouped route files for app areas
require __DIR__ . '/blogs.php';
require __DIR__ . '/admin/users.php';
require __DIR__ . '/admin/categories.php';
require __DIR__ . '/admin/stats.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/i18n.php';
require __DIR__ . '/auth.php';

// Public routes must be loaded last
require __DIR__ . '/public.php';
