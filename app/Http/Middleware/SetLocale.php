<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App as AppFacade;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array)config('app.supported_locales', []);

        $locale =
            optional($request->user())->locale
            ?? $request->session()->get('locale')
            ?? $request->cookie('locale');

        if ($locale === null) {
            $overrideAcceptLanguage = (bool)config('app.locale_override_accept_language');

            $locale = $overrideAcceptLanguage
                ? (string)config('app.locale')
                : ($request->getPreferredLanguage($supported) ?: (string)config('app.locale'));
        }

        if (!in_array($locale, $supported, true)) {
            $locale = (string)config('app.locale');
        }

        AppFacade::setLocale($locale);

        return $next($request);
    }
}
