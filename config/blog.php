<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta Description Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for generating meta descriptions from content.
    |
    */

    'meta_description_length' => 160,
    'meta_description_min_cut_position' => 80,

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default and maximum page sizes for blog post listings.
    |
    */

    'default_page_size' => 10,
    'max_page_size' => 100,

    // Time block for repeated view from the same device/user/page combination
    'page_view_block_seconds' => env('PAGE_VIEW_BLOCK_SECONDS', 3600),
];
