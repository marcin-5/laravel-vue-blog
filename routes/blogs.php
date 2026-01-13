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

    // Post Extensions (New pivot-based routes)
    Route::prefix('blogger/blogs/{blog}/posts/{post}')->group(function () {
        Route::get('extensions/available', [PostsController::class, 'availableExtensions'])
            ->name('blogger.posts.extensions.available');
        Route::post('extensions', [PostsController::class, 'attachExtension'])
            ->name('blogger.posts.extensions.attach');
        Route::delete('extensions/{extensionPostId}', [PostsController::class, 'detachExtension'])
            ->name('blogger.posts.extensions.detach');
        Route::put('extensions/reorder', [PostsController::class, 'reorderExtensions'])
            ->name('blogger.posts.extensions.reorder');
    });

    // Markdown preview route
    Route::post('markdown/preview', [MarkdownController::class, 'preview'])->name('markdown.preview');
});
