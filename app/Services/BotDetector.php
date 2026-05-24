<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

readonly class BotDetector
{
    /**
     * Return detector-reported bot name, or null when not a bot.
     */
    public function getBotName(Request|string|null $requestOrUa): ?string
    {
        $ua = $this->extractUserAgent($requestOrUa);
        if ($ua === '') {
            return null;
        }

        $cd = new CrawlerDetect;
        if (!$cd->isCrawler($ua)) {
            return null;
        }

        $name = $cd->getMatches();

        return $name !== null && $name !== '' ? $name : 'Bot';
    }

    private function extractUserAgent(Request|string|null $requestOrUa): string
    {
        if ($requestOrUa instanceof Request) {
            return (string) $requestOrUa->header('User-Agent', '');
        }

        return (string) ($requestOrUa ?? '');
    }

    /**
     * Quick bot check using Jaybizzle CrawlerDetect signatures.
     */
    public function isBot(Request|string|null $requestOrUa): bool
    {
        $ua = $this->extractUserAgent($requestOrUa);
        if ($ua === '') {
            return false;
        }

        $cd = new CrawlerDetect;

        return $cd->isCrawler($ua);
    }
}
