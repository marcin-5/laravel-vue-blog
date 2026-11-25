<?php

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

it('shares common and public translations for guests', function () {
    App::setLocale('en');

    $response = $this->get('/');

    $response->assertInertia(fn(Assert $page) => $page
        ->has('translations')
        ->where('translations.locale', 'en')
        ->has('translations.messages'),
    );
});

it('shares common and app translations for authenticated users', function () {
    App::setLocale('en');
    $user = User::factory()->create();
    Cache::put('inertia.translations.en.app', [], 3600);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn(Assert $page) => $page
        ->has('translations')
        ->where('translations.locale', 'en')
        ->where('translations.messages.blogger.form.theme_font_header_alternative', 'Alternative Header Font')
        ->where('translations.messages.blogger.form.theme_font_body_alternative', 'Alternative Body Font')
        ->where('translations.messages.blogger.form.theme_font_none', 'None'),
    );
});
