<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Blogger\BlogsController;
use App\Http\Controllers\Blogger\PostsController;

// Blogger routes for managing own blogs
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('blogs', BlogsController::class)
        ->only(['index', 'store', 'update']);

    // Posts routes (create only for now)
    Route::post('posts', [PostsController::class, 'store'])->name('posts.store');
});
