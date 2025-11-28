<?php

use App\Http\Controllers\Concerns\ValidatesLocale;

it('returns given locale when it is available', function () {
    config(['app.available_locales' => ['en', 'pl']]);

    $instance = new class
    {
        use ValidatesLocale;

        public function callValidateAndGetLocale(?string $locale = null): string
        {
            return $this->validateAndGetLocale($locale);
        }
    };

    expect($instance->callValidateAndGetLocale('pl'))->toBe('pl');
});

it('falls back to app locale when argument is null', function () {
    config(['app.available_locales' => ['en', 'pl']]);
    app()->setLocale('pl');

    $instance = new class
    {
        use ValidatesLocale;

        public function callValidateAndGetLocale(?string $locale = null): string
        {
            return $this->validateAndGetLocale($locale);
        }
    };

    expect($instance->callValidateAndGetLocale())->toBe('pl');
});

it('returns fallback locale when given locale is not available', function () {
    config([
        'app.available_locales' => ['en', 'pl'],
        'app.fallback_locale' => 'en',
    ]);

    $instance = new class
    {
        use ValidatesLocale;

        public function callValidateAndGetLocale(?string $locale = null): string
        {
            return $this->validateAndGetLocale($locale);
        }
    };

    expect($instance->callValidateAndGetLocale('de'))->toBe('en');
});
