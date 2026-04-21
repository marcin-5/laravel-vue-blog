<?php

use App\Http\Controllers\EnneagramTestController;
use Illuminate\Support\Facades\Route;

Route::domain('enneagram-test.localhost')->group(function () {
    Route::get('/', [EnneagramTestController::class, 'index']);
});
