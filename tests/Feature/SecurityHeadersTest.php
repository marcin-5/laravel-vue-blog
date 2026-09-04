<?php

use Illuminate\Support\Facades\Config;

test('adds security response headers', function () {
    $this->app->detectEnvironment(fn(): string => 'production');

    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=(), payment=(), usb=()')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
});

test('does not add HSTS outside production', function () {
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
});

test('publishes a security contact document', function () {
    Config::set('mail.contact_to', 'security@example.com');

    $response = $this->get('/.well-known/security.txt');

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertSee('Contact: mailto:security@example.com', false)
        ->assertSee('Preferred-Languages: pl, en', false)
        ->assertSee('Expires: ', false);
});
