<?php

use App\Http\Controllers\EnneagramTestApiController;
use App\Http\Controllers\EnneagramTestController;
use Illuminate\Support\Facades\Route;

$enneagramDomains = config('enneagram.domains');
$enneagramDomainPattern = $enneagramDomains
        |> array_keys(...)
        |> (fn($x) => array_map(fn(string $domain): string => preg_quote($domain, '#'), $x))
        |> (fn($x) => implode('|', $x));

Route::domain('{enneagramDomain}')
    ->where(['enneagramDomain' => $enneagramDomainPattern])
    ->group(function () {
        Route::get('/', [EnneagramTestController::class, 'index'])
            ->name('enneagram.test');

        Route::post('/start', [EnneagramTestApiController::class, 'start'])
            ->name('enneagram.test.start')
            ->middleware('throttle:120,1');

        Route::post('/action', [EnneagramTestApiController::class, 'action'])
            ->name('enneagram.test.action')
            ->middleware('throttle:120,1');

        Route::post('/reset', [EnneagramTestApiController::class, 'reset'])
            ->name('enneagram.test.reset')
            ->middleware('throttle:120,1');

        // Fallback for this domain to prevent public routes from leaking here
        Route::any('{any}', function () {
            abort(404);
        })->where('any', '.*');
    });

foreach (['start', 'action', 'reset'] as $endpoint) {
    Route::any("/$endpoint", function () {
        abort(404);
    });
}
