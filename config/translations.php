<?php

return [
    // Map page types used by public routes to translation groups.
    // These groups are merged in order. Missing files are skipped silently.
    'page_groups' => [
        'home' => ['public', 'common'],
        'about' => ['public', 'common'],
        'contact' => ['public', 'common'],
        'blog' => ['public', 'common'],
        'post' => ['public', 'common'],
        'auth' => ['auth', 'common'],
    ],

    // When true, also merge base messages from resources/lang/{locale}.json
    'include_root_json' => true,

    // Optional shallow cache (seconds) to avoid repeated disk IO for the same request bursts.
    // Set to 0 to disable in development if you want immediate reload of changes.
    'cache_ttl' => env('TRANSLATIONS_CACHE_TTL', 0),
];
