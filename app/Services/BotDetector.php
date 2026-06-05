<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

readonly class BotDetector
{
    public function __construct(
        private CrawlerDetect $crawlerDetect = new CrawlerDetect,
    ) {}

    /**
     * Return detector-reported bot name, or null when not a bot.
     */
    public function getBotName(Request|string|null $requestOrUa): ?string
    {
        $userAgent = $this->extractUserAgent($requestOrUa);

        if ($userAgent === '' || !$this->crawlerDetect->isCrawler($userAgent)) {
            return null;
        }

        $name = $this->crawlerDetect->getMatches();

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
        $userAgent = $this->extractUserAgent($requestOrUa);

        return $userAgent !== '' && $this->crawlerDetect->isCrawler($userAgent);
    }
}
