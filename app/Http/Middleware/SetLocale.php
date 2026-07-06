<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App as AppFacade;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public static function getAppNameForLocale(string $locale): string
    {
        return $locale === 'pl' ? 'Osobliwy Blog' : 'Peculiar Matters';
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('app.supported_locales', []);
        $domainLocales = (array) config('app.domain_locales', []);
        $host = $request->getHost();

        $locale = $domainLocales[$host] ?? null;

        if ($locale === null) {
            foreach ($domainLocales as $domain => $l) {
                if (str_ends_with($host, '.' . $domain)) {
                    $locale = $l;
                    break;
                }
            }
        }

        if ($locale === null) {
            $locale =
                ($request->user() ? $request->user()->locale : null)
                ?? ($request->hasSession() ? $request->session()->get('locale') : null)
                ?? $request->cookie('locale');
        }

        if ($locale === null) {
            $overrideAcceptLanguage = (bool) config('app.locale_override_accept_language');

            $locale = $overrideAcceptLanguage
                ? (string) config('app.locale')
                : ($request->getPreferredLanguage($supported) ?: (string) config('app.locale'));
        }

        if (!in_array($locale, $supported, true)) {
            $locale = (string) config('app.locale');
        }

        AppFacade::setLocale($locale);

        config([
            'app.name' => self::getAppNameForLocale($locale),
        ]);

        return $next($request);
    }
}
