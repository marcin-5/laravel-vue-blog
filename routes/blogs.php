<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Blogger\BlogsController;

// Blogger routes for managing own blogs
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('blogs', BlogsController::class)
        ->only(['index', 'store', 'update']);
});
