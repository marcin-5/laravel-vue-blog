<?php

use App\Http\Controllers\Blogger\BlogsController;
use App\Http\Controllers\Blogger\PostsController;
use App\Http\Controllers\Blogger\StatsController as BloggerStatsController;
use App\Http\Controllers\MarkdownController;
use App\Http\Controllers\PostExtensionController;
use Illuminate\Support\Facades\Route;

// Blogger routes for managing own blogs
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('blogs', BlogsController::class)
        ->only(['index', 'store', 'update']);

    // Blogger stats
    Route::get('blogs/stats', [BloggerStatsController::class, 'index'])->name('blogger.stats.index');

    // Posts routes
    Route::post('posts', [PostsController::class, 'store'])->name('posts.store');
    Route::patch('posts/{post}', [PostsController::class, 'update'])->name('posts.update');

    // Post Extensions
    Route::post('posts/{post}/extensions', [PostExtensionController::class, 'store'])->name('post-extensions.store');
    Route::patch('post-extensions/{extension}', [PostExtensionController::class, 'update'])->name(
        'post-extensions.update',
    );
    Route::delete('post-extensions/{extension}', [PostExtensionController::class, 'destroy'])->name(
        'post-extensions.destroy',
    );

    // Markdown preview route
    Route::post('markdown/preview', [MarkdownController::class, 'preview'])->name('markdown.preview');
});
