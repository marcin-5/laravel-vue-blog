<?php

use App\Http\Controllers\Blogger\BlogsController;
use App\Http\Controllers\Blogger\PostsController;
use App\Http\Controllers\MarkdownController;
use Illuminate\Support\Facades\Route;

// Blogger routes for managing own blogs
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('blogs', BlogsController::class)
        ->only(['index', 'store', 'update']);

    // Posts routes
    Route::post('posts', [PostsController::class, 'store'])->name('posts.store');
    Route::patch('posts/{post}', [PostsController::class, 'update'])->name('posts.update');

    // Markdown preview route
    Route::post('markdown/preview', [MarkdownController::class, 'preview'])->name('markdown.preview');
});
