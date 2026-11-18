<?php

use App\Http\Controllers\Admin\LogsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth', 'verified', 'can:view_admin_stats'])
    ->name('admin.')
    ->group(function () {
        Route::get('logs', [LogsController::class, 'index'])
            ->name('logs.index');
    });
