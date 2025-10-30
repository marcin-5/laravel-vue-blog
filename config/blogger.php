<?php

return [
    'defaults' => [
        'locale' => 'en',
        'sidebar' => 0,
        'page_size' => 10,
    ],

    'limits' => [
        'sidebar' => [
            'min' => -50,
            'max' => 50,
        ],
        'page_size' => [
            'min' => 1,
            'max' => 100,
        ],
    ],

    'supported_locales' => ['en', 'pl'],

    'posts' => [
        'defaults' => [
            'is_published' => false,
            'visibility' => 'public',
        ],

        'allowed_visibility' => ['public', 'registered'],

        'limits' => [
            'excerpt_max_length' => 500,
            'title_max_length' => 255,
        ],
    ],
];
