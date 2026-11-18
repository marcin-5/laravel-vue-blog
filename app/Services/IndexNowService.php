<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\IndexNowItem;
use App\Models\IndexNowSubmission;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

readonly class IndexNowService
{
    public function __construct(
        private RobotsParser $robotsParser,
    ) {}

    /**
     * Submit a list of URLs to the IndexNow API.
     *
     * @param  array<IndexNowItem>  $items  The list of URLs and their model context.
     */
    public function submitUrls(array $items): bool
    {
        $endpoint = 'https://api.indexnow.org/indexnow';

        if (app()->environment('local') && !app()->environment('testing')) {
            Log::info('IndexNow submission skipped in local environment.', ['urls' => array_map(fn(IndexNowItem $item) => $item->url, $items)]);
            return true;
        }

        $urlsByHost = [];
        foreach ($items as $item) {
            $host = parse_url($item->url, PHP_URL_HOST);

            if (!$host) {
                Log::error('IndexNow submission failed: URL has no host.', ['url' => $item->url]);
                $this->recordSubmission($item, false, null, 'URL has no host.');
                $allSuccessful = false;
                continue;
            }

            $urlsByHost[$host][$item->url] ??= $item;
        }

        if (!$urlsByHost) {
            return isset($allSuccessful) ? $allSuccessful : false;
        }

        $key = $this->getApiKey();
        if (app()->runningUnitTests() && !$key) {
            $key = 'test-key';
        }

        if (!$key) {
            Log::error('IndexNow submission failed: Missing API Key or App Host.');
            foreach ($urlsByHost as $hostItems) {
                foreach ($hostItems as $item) {
                    $this->recordSubmission($item, false, null, 'Missing API Key or App Host.');
                }
            }
            return false;
        }

        $this->ensureKeyFileExists();

        $allSuccessful ??= true;

        foreach ($urlsByHost as $host => $hostItems) {
            $hostUrls = array_keys($hostItems);
            $scheme = parse_url($hostUrls[0], PHP_URL_SCHEME) ?: 'https';
            $keyLocation = $scheme . '://' . $host . '/' . $key . '.txt';
            $payload = [
                'host' => $host,
                'key' => $key,
                'keyLocation' => $keyLocation,
                'urlList' => $hostUrls,
            ];

            try {
                $response = Http::post($endpoint, $payload);

                Log::info('IndexNow API response', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'host' => $host,
                    'urls_count' => count($hostUrls),
                ]);

                foreach ($hostItems as $item) {
                    $this->recordSubmission(
                        $item,
                        $response->successful(),
                        $response->status(),
                        $response->successful() ? null : $response->body(),
                    );
                }

                $allSuccessful = $allSuccessful && $response->successful();
            } catch (Exception $e) {
                Log::error('IndexNow API request failed: ' . $e->getMessage(), ['host' => $host]);
                foreach ($hostItems as $item) {
                    $this->recordSubmission($item, false, null, $e->getMessage());
                }
                $allSuccessful = false;
            }
        }

        return $allSuccessful;
    }

    private function recordSubmission(IndexNowItem $item, bool $succeeded, ?int $httpStatus, ?string $errorMessage): void
    {
        IndexNowSubmission::create([
            'url' => $item->url,
            'blog_id' => $item->blogId,
            'post_id' => $item->postId,
            'succeeded' => $succeeded,
            'http_status' => $httpStatus,
            'error_message' => $errorMessage,
            'submitted_at' => now(),
        ]);
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
            Log::info("IndexNow key file created at $path");
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
        return $this->robotsParser->isAllowed($url, 'IndexNow');
    }
}
