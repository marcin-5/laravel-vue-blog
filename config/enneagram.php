<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enneagram Test Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, additional debugging options will be available on the
    | frontend for the Enneagram test.
    |
    */
    'debug' => env('ENNEAGRAM_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Enneagram Test Domain
    |--------------------------------------------------------------------------
    |
    | The domain or subdomain where the Enneagram test is hosted.
    |
    */
    'domain' => env('ENNEAGRAM_DOMAIN', 'enneagram-test.localhost'),
];
