<?php

use App\Http\Controllers\AuthenticatedController;

it('registers auth and verified middleware in constructor', function () {
    $controller = new AuthenticatedController;

    $middleware = collect($controller->getMiddleware())
        ->pluck('middleware')
        ->flatten()
        ->all();

    expect($middleware)
        ->toContain('auth')
        ->toContain('verified');
});
