<?php

namespace App\Http\Controllers\Concerns;

trait ValidatesLocale
{
    /**
     * Validate the given locale (or the current application locale) against the configured
     * list of available locales and return a safe locale string.
     */
    protected function validateAndGetLocale(?string $locale = null): string
    {
        $localeToCheck = $locale ?? app()->getLocale();

        $availableLocales = (array) config('app.available_locales', ['en', 'pl']);

        if (in_array($localeToCheck, $availableLocales, true)) {
            return $localeToCheck;
        }

        return (string) config('app.fallback_locale', 'en');
    }
}
