<?php

use App\Http\Controllers\EnneagramTestController;
use Illuminate\Support\Facades\Route;

Route::domain(config('enneagram.domain'))->group(function () {
    Route::get('/', [EnneagramTestController::class, 'index']);
});
