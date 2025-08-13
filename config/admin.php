<?php

return [
    // Name for the initial administrator account
    'name' => env('ADMIN_NAME', 'Administrator'),

    // Email for the initial administrator account
    'email' => env('ADMIN_EMAIL', 'admin@example.com'),

    // Password for the initial administrator account
    // In production, ALWAYS set this via the environment and DO NOT commit .env files.
    // If null, the seeder may fall back to a generated or default password (see seeder).
    'password' => env('ADMIN_PASSWORD', null),

    // Whether to mark the admin's email as verified
    'verify_email' => env('ADMIN_VERIFY_EMAIL', false),
];
