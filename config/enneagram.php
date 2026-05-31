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
    | Auto confirm single-answer questions
    |--------------------------------------------------------------------------
    |
    | When enabled, selecting an answer in single-choice questions will
    | automatically confirm and advance to the next question. This can be
    | overridden by the user on the test start screen.
    |
    */
    'auto_confirm_single' => env('ENNEAGRAM_AUTO_CONFIRM_SINGLE', true),

    /*
    |--------------------------------------------------------------------------
    | Enneagram Test Domains
    |--------------------------------------------------------------------------
    |
    | The domains or subdomains where the Enneagram test is hosted and their
    | associated locales.
    |
    */
    'domains' => [
        env('ENNEAGRAM_DOMAIN_PL', 'enneagram-test.osobliwy.localhost') => 'pl',
        env('ENNEAGRAM_DOMAIN_EN', 'enneagram-test.peculiarmatters.localhost') => 'en',
    ],
];
