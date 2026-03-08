<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

        $isAuthenticated = Auth::check();
        $cacheKey = "inertia.translations.{$locale}." . ($isAuthenticated ? 'app' : 'public');

        $messages = Cache::remember($cacheKey, 3600, function () use ($locale, $isAuthenticated) {
            return $this->getMergedMessages($locale, $isAuthenticated);
        });

        Inertia::share([
            'translations' => [
                'locale' => $locale,
                'messages' => $messages,
            ],
        ]);

        return $next($request);
    }

    private function getMergedMessages(string $locale, bool $isAuthenticated): array
    {
        $common = $this->loadJsonFile($locale, 'common');
        $authTranslations = $this->loadJsonFile($locale, 'auth');
        $specificFile = $isAuthenticated ? 'app' : 'public';
        $specific = $this->loadJsonFile($locale, $specificFile);

        return array_merge($common, $authTranslations, $specific);
    }

    private function loadJsonFile(string $locale, string $file): array
    {
        $path = resource_path("lang/{$locale}/{$file}.json");

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $data ?: [];
    }
}
