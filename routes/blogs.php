<?php

use App\Http\Controllers\Blogger\BlogsController;
use App\Http\Controllers\Blogger\PostsController;
use Illuminate\Support\Facades\Route;

// Blogger routes for managing own blogs
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('blogs', BlogsController::class)
        ->only(['index', 'store', 'update']);

    // Posts routes
    Route::post('posts', [PostsController::class, 'store'])->name('posts.store');
    Route::patch('posts/{post}', [PostsController::class, 'update'])->name('posts.update');
    Route::post('posts/preview', [PostsController::class, 'preview'])->name('posts.preview');

    // Blog preview route
    Route::post('blogs/preview', [BlogsController::class, 'preview'])->name('blogs.preview');
});
