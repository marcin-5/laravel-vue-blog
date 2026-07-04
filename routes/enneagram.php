<?php

use App\Http\Controllers\EnneagramTestController;
use Illuminate\Support\Facades\Route;

$enneagramDomains = config('enneagram.domains');

foreach ($enneagramDomains as $domain => $locale) {
    Route::domain($domain)->group(function () {
        Route::get('/', [EnneagramTestController::class, 'index']);

        // Fallback for this domain to prevent public routes from leaking here
        Route::any('{any}', function () {
            abort(404);
        })->where('any', '.*');
    });
}
