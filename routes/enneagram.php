<?php

use App\Http\Controllers\EnneagramTestController;
use Illuminate\Support\Facades\Route;

$enneagramDomains = config('enneagram.domains');

foreach ($enneagramDomains as $domain => $locale) {
    Route::domain($domain)->group(function () {
        Route::get('/', [EnneagramTestController::class, 'index']);
    });
}
