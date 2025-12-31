<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HandleTranslations
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = App::getLocale();

        Inertia::share([
            'translations' => function () use ($locale) {
                $commonPath = resource_path("lang/{$locale}/common.json");
                $common = file_exists($commonPath) ? json_decode(file_get_contents($commonPath), true) : [];

                $authPath = resource_path("lang/{$locale}/auth.json");
                $auth = file_exists($authPath) ? json_decode(file_get_contents($authPath), true) : [];

                if (Auth::check()) {
                    $appPath = resource_path("lang/{$locale}/app.json");
                    $app = file_exists($appPath) ? json_decode(file_get_contents($appPath), true) : [];

                    return [
                        'locale' => $locale,
                        'messages' => array_merge($common, $app, $auth),
                    ];
                }

                $publicPath = resource_path("lang/{$locale}/public.json");
                $public = file_exists($publicPath) ? json_decode(file_get_contents($publicPath), true) : [];

                return [
                    'locale' => $locale,
                    'messages' => array_merge($common, $public, $auth),
                ];
            },
        ]);

        return $next($request);
    }
}
