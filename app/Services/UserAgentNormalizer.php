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
        $cd = new CrawlerDetect;
        if ($cd->isCrawler($userAgent)) {
            $botName = $cd->getMatches();

            return !empty($botName) ? Str::ucfirst($botName) : 'Bot';
        }

        $parser = Parser::create();
        $result = $parser->parse($userAgent);

        $uaFamily = $result->ua->family ?? 'Unknown';
        $osFamily = $result->os->family ?? 'Unknown';
        $deviceFamily = $result->device->family ?? 'Unknown';

        return trim("$uaFamily on $osFamily ($deviceFamily) ");
    }
}
