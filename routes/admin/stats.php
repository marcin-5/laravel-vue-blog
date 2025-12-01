<?php

use App\Http\Controllers\Admin\StatsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth', 'verified'])
    ->name('admin.')
    ->group(function () {
        Route::get('stats', [StatsController::class, 'index'])
            ->middleware('can:view-admin-stats')
            ->name('stats.index');
    });
