<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    //
});

it('uses app.locale when override_accept_language is true and no other source is present', function () {
    config()->set('app.locale', 'en');
    config()->set('app.supported_locales', ['en', 'pl']);
    config()->set('app.locale_override_accept_language', true);

    $this->getJson('/_test/locale', ['Accept-Language' => 'pl'])
        ->assertOk()
        ->assertJson(['locale' => 'en']);
});

it('uses browser preferred language when override_accept_language is false', function () {
    config()->set('app.locale', 'en');
    config()->set('app.supported_locales', ['en', 'pl']);
    config()->set('app.locale_override_accept_language', false);

    $this->getJson('/_test/locale', ['Accept-Language' => 'pl'])
        ->assertOk()
        ->assertJson(['locale' => 'pl']);
});

it('prefers session locale over cookie, accept-language and config', function () {
    config()->set('app.locale', 'en');
    config()->set('app.supported_locales', ['en', 'pl']);
    config()->set('app.locale_override_accept_language', false);

    $this->withSession(['locale' => 'pl'])
        ->withCookie('locale', 'en')
        ->getJson('/_test/locale', ['Accept-Language' => 'en'])
        ->assertOk()
        ->assertJson(['locale' => 'pl']);
});

it('prefers cookie locale over accept-language and config', function () {
    config()->set('app.locale', 'en');
    config()->set('app.supported_locales', ['en', 'pl']);
    config()->set('app.locale_override_accept_language', false);

    $this->call('GET', '/_test/locale', [], ['locale' => 'pl'], [], ['HTTP_ACCEPT_LANGUAGE' => 'en'])
        ->assertOk()
        ->assertJson(['locale' => 'pl']);
});

it('falls back to app.locale when selected locale is not supported', function () {
    config()->set('app.locale', 'en');
    config()->set('app.supported_locales', ['en', 'pl']);
    config()->set('app.locale_override_accept_language', false);

    $this->withSession(['locale' => 'de'])
        ->getJson('/_test/locale', ['Accept-Language' => 'pl'])
        ->assertOk()
        ->assertJson(['locale' => 'en']);
});

it('prefers authenticated user locale over session, cookie, accept-language and config', function () {
    config()->set('app.locale', 'en');
    config()->set('app.supported_locales', ['en', 'pl']);
    config()->set('app.locale_override_accept_language', false);

    $user = User::factory()->create(['locale' => 'pl']);

    $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->withCookie('locale', 'en')
        ->getJson('/_test/locale', ['Accept-Language' => 'en'])
        ->assertOk()
        ->assertJson(['locale' => 'pl']);
});
