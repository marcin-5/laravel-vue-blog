<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sitemap TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | The time in seconds before the sitemap is considered stale and should
    | be regenerated. Default is 3600 seconds (1 hour).
    |
    */

    'ttl' => (int)env('SITEMAP_TTL', 3600),
];
