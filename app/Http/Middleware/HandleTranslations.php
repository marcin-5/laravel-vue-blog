<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class HandleTranslations
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = App::getLocale();

        $isAuthenticated = Auth::check();
        $specificFile = $isAuthenticated ? 'app' : 'public';
        $translationFiles = ['common', 'auth', $specificFile];
        $translationVersion = array_map(
            static function (string $file) use ($locale): string {
                $path = resource_path("lang/$locale/$file.json");

                if (!is_file($path)) {
                    return '';
                }

                $hash = hash_file('sha256', $path);

                return $hash === false ? '' : $hash;
            },
            $translationFiles,
        )
                |> (static fn($x) => implode('|', $x))
                |> (static fn($x) => hash('sha256', $x));
        $cacheKey = "inertia.translations.$locale.$specificFile.$translationVersion";

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
        $path = resource_path("lang/$locale/$file.json");

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return $data ?: [];
    }
}
