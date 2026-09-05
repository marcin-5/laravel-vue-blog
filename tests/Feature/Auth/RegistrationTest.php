<?php

use Illuminate\Support\Facades\Config;

test('registration screen can be rendered', function () {
    Config::set('auth.registration_enabled', true);

    $response = $this->get('/register');

    $response->assertInertia(fn($page) => $page
        ->component('app/auth/Register')
        ->where('registrationEnabled', true)
    );
});

test('registration screen is unavailable when configuration contains false as text', function () {
    Config::set('auth.registration_enabled', 'false');

    $response = $this->get('/register');

    $response->assertInertia(fn($page) => $page
        ->component('app/auth/Register')
        ->where('registrationEnabled', false)
    );
});

test('new users can register', function () {
    Config::set('auth.registration_enabled', true);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration is rejected when disabled', function () {
    Config::set('auth.registration_enabled', false);

    $response = $this->post('/register', [
        'name' => 'Blocked User',
        'email' => 'blocked@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertNotFound();
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
});
