<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoriesController;

Route::prefix('admin')
    ->middleware(['auth', 'verified'])
    ->name('admin.')
    ->group(function () {
        Route::resource('categories', CategoriesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
    });
