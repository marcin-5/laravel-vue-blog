<?php

use App\Http\Controllers\Admin\BlogsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth', 'verified', 'can:view_admin_users'])
    ->name('admin.')
    ->group(function () {
        Route::patch('blogs/{blog}/bing-verification-token', [BlogsController::class, 'updateBingVerificationToken'])
            ->name('blogs.bing-verification-token.update');
    });
