<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('signed url is valid when generated and accessed normally', function () {
    $email = 'test@example.com';
    $relativeSigned = URL::signedRoute('newsletter.manage', ['email' => $email], absolute: false);
    $url = url($relativeSigned);

    $response = $this->get($url);

    $response->assertStatus(200);
});

test('signed url is invalid if email is tampered', function () {
    $email = 'test@example.com';
    $relativeSigned = URL::signedRoute('newsletter.manage', ['email' => $email], absolute: false);
    $url = url($relativeSigned);

    // Tamper with email in the query string
    $tamperedUrl = str_replace('test%40example.com', 'tampered%40example.com', $url);

    $response = $this->get($tamperedUrl);

    $response->assertStatus(403);
});

test('signed url might fail if host mismatch with default check, but pass with relative check', function () {
    // Force APP_URL for generation
    config(['app.url' => 'http://localhost:8000']);

    $email = 'test@example.com';
    // Generate with relative signature (what we implemented)
    $relativeSigned = URL::signedRoute('newsletter.manage', ['email' => $email], absolute: false);
    $url = url($relativeSigned);

    // Attempt to access via different host/port
    $response = $this->get($url, [
        'HTTP_HOST' => 'localhost:80',
    ]);

    // Now it should be 200 because we use hasValidSignature(false) in the controller
    $response->assertStatus(200);
});
