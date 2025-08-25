<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UsersController;

Route::prefix('admin')
    ->middleware(['auth', 'verified'])
    ->name('admin.')
    ->group(function () {
        // Apply permission middleware only to users management
        Route::middleware(['can:edit-user-blog-quota'])->group(function () {
            Route::resource('users', UsersController::class)
                ->only(['index', 'update']);
        });
    });
