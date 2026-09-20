<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\PostThreadController;
use App\Http\Controllers\ThreadCommentController;
use App\Http\Middleware\RedirectGuestToGroupRegistration;
use Illuminate\Support\Facades\Route;

// Test-only helper route to verify locale resolution in middleware
if (app()->environment('testing')) {
    Route::middleware('web')->get('/_test/locale', function () {
        return response()->json(['locale' => app()->getLocale()]);
    });
}

// Route::get('/', function () {
//    return Inertia::render('Welcome');
// })->name('home');

Route::middleware(['auth', 'verified', 'noindex'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('_')->group(function () {
        Route::get('/{group:slug}', [GroupController::class, 'landing'])
            ->middleware(RedirectGuestToGroupRegistration::class)
            ->name('group.landing');
        Route::get('/{group:slug}/{postSlug}', [GroupController::class, 'post'])->name('group.post');
    });
});

Route::get('posts/{post}/threads', [PostThreadController::class, 'index'])
    ->name('posts.threads.index');
Route::post('posts/{post}/threads', [PostThreadController::class, 'store'])
    ->middleware('auth')
    ->name('posts.threads.store');
Route::get('threads/{thread}/comments', [ThreadCommentController::class, 'index'])
    ->name('threads.comments.index');
Route::post('threads/{thread}/comments', [ThreadCommentController::class, 'store'])
    ->middleware('auth')
    ->name('threads.comments.store');
Route::patch('comments/{comment}', [ThreadCommentController::class, 'update'])
    ->middleware('auth')
    ->name('comments.update');
Route::delete('comments/{comment}', [ThreadCommentController::class, 'destroy'])
    ->middleware('auth')
    ->name('comments.destroy');
Route::delete('threads/{thread}', [PostThreadController::class, 'destroy'])
    ->middleware('auth')
    ->name('threads.destroy');

// Grouped route files for app areas
require __DIR__ . '/blogs.php';
require __DIR__ . '/admin/users.php';
require __DIR__ . '/admin/categories.php';
require __DIR__ . '/admin/stats.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/i18n.php';
require __DIR__ . '/auth.php';

// Public routes must be loaded last
require __DIR__ . '/enneagram.php';
require __DIR__ . '/public.php';
