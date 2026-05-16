<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;

readonly class RobotsParser
{
    /**
     * Check if the given URL is allowed by robots.txt.
     * @throws FileNotFoundException
     */
    public function isAllowed(string $url, string $userAgent = '*'): bool
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
                $userAgentApplies = ($ua === '*' || stripos($ua, $userAgent) !== false || $this->isSearchEngineBot(
                    $ua,
                ));
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

    private function isSearchEngineBot(string $ua): bool
    {
        return stripos($ua, 'IndexNow') !== false || stripos($ua, 'Bingbot') !== false || stripos(
            $ua,
            'Googlebot',
        ) !== false;
    }
}
