<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    /**
     * Submit a list of URLs to the IndexNow API engines.
     *
     * @param  array<string>  $urls  The list of URLs to submit.
     * @param  string|null  $engine  The engine to submit to (bing, yandex, or null for both).
     */
    public function submitUrls(array $urls, ?string $engine = null): bool
    {
        $engines = [
            'bing' => 'https://api.indexnow.org/indexnow',
            'yandex' => 'https://yandex.com/indexnow',
        ];

        $targetEngines = $engine ? [$engine => $engines[$engine] ?? null] : $engines;

        if (app()->environment('local') && !app()->runningUnitTests()) {
            Log::info('IndexNow submission skipped in local environment.', ['urls' => $urls, 'engine' => $engine]);
            return true;
        }

        $key = $this->getApiKey();
        if (app()->runningUnitTests() && !$key) {
            $key = 'test-key';
        }
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        if (app()->runningUnitTests() && !$host) {
            $host = 'example.org';
        }

        if (!$key || !$host) {
            Log::error('IndexNow submission failed: Missing API Key or App Host.');
            return false;
        }

        $this->ensureKeyFileExists();

        $keyLocation = url($key . '.txt');

        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => $keyLocation,
            'urlList' => $urls,
        ];

        $allSuccessful = true;

        foreach ($targetEngines as $name => $endpoint) {
            if (!$endpoint) {
                Log::warning("IndexNow engine '{$name}' not found.");
                continue;
            }

            try {
                $response = Http::post($endpoint, $payload);

                Log::info("IndexNow API response from {$name}", [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'urls_count' => count($urls),
                ]);

                if (!in_array($response->status(), [200, 202])) {
                    $allSuccessful = false;
                }
            } catch (Exception $e) {
                Log::error("IndexNow API request failed for {$name}: " . $e->getMessage());
                $allSuccessful = false;
            }
        }

        return $allSuccessful;
    }

    /**
     * Get the IndexNow API key from configuration.
     */
    public function getApiKey(): ?string
    {
        return config('services.indexnow.key');
    }

    /**
     * Ensure the UTF-8 key file exists at the root of the website.
     */
    public function ensureKeyFileExists(): void
    {
        $key = $this->getApiKey();
        if (!$key) {
            return;
        }

        $path = public_path($key . '.txt');
        if (!File::exists($path)) {
            File::put($path, $key);
            Log::info("IndexNow key file created at {$path}");
        }
    }

    /**
     * Check if a URL should be submitted (is published and not restricted).
     */
    public function shouldSubmit(string $url, string $visibility, bool $isPublished): bool
    {
        $restrictedVisibilities = ['unlisted', 'extension', 'registered', 'restricted'];

        if (!$isPublished) {
            return false;
        }

        if (in_array($visibility, $restrictedVisibilities)) {
            return false;
        }

        return $this->isAllowedByRobots($url);
    }

    /**
     * Check if the given URL is allowed by robots.txt.
     */
    public function isAllowedByRobots(string $url): bool
    {
        $robotsPath = public_path('robots.txt');
        if (!File::exists($robotsPath)) {
            return true;
        }

        $robotsContent = File::get($robotsPath);
        $parsedUrl = parse_url($url);
        $path = ($parsedUrl['path'] ?? '/') . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');

        $lines = explode("\n", $robotsContent);
        $userAgentApplies = true;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (stripos($line, 'User-agent:') === 0) {
                $ua = trim(substr($line, 11));
                $userAgentApplies = ($ua === '*' || stripos($ua, 'IndexNow') !== false || stripos(
                        $ua,
                        'Bingbot',
                    ) !== false);
                continue;
            }

            if ($userAgentApplies && stripos($line, 'Disallow:') === 0) {
                $disallowPath = trim(substr($line, 9));
                if (!empty($disallowPath) && str_starts_with($path, $disallowPath)) {
                    return false;
                }
            }
        }

        return true;
    }
}
