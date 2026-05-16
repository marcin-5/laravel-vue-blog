<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use UAParser\Exception\FileNotFoundException;
use UAParser\Parser;

readonly class UserAgentNormalizer
{
    /**
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function normalize(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return 'Unknown';
        }

        // Prefer Jaybizzle CrawlerDetect for bot identification
        $cd = new CrawlerDetect();
        if ($cd->isCrawler($userAgent)) {
            $botName = $cd->getMatches();
            if (!empty($botName)) {
                return Str::ucfirst($botName);
            }

            // Fallback to configured fragments if CrawlerDetect lacks a friendly name
            $fragments = config('bots.fragments', []);
            if (Str::contains($userAgent, $fragments, ignoreCase: true)) {
                $sortedFragments = self::getSortedBotFragments();
                $matchingFragment = collect($sortedFragments)
                    ->first(fn(string $fragment) => Str::contains($userAgent, $fragment, ignoreCase: true));
                if ($matchingFragment !== null) {
                    return Str::ucfirst($matchingFragment);
                }
            }

            return 'Bot';
        }

        $parser = Parser::create();
        $result = $parser->parse($userAgent);

        $uaFamily = $result->ua->family ?? 'Unknown';
        $osFamily = $result->os->family ?? 'Unknown';
        $deviceFamily = $result->device->family ?? 'Unknown';

        return trim("$uaFamily on $osFamily ($deviceFamily) ");
    }

    /**
     * @return array<int, string>
     */
    public static function getSortedBotFragments(): array
    {
        return collect(config('bots.fragments', []))
            ->sortByDesc(static fn(string $fragment) => strlen($fragment))
            ->values()
            ->all();
    }
}
