<?php

namespace App\Services;

use Illuminate\Support\Str;
use UAParser\Exception\FileNotFoundException;
use UAParser\Parser;

readonly class UserAgentNormalizer
{
    /**
     * @throws FileNotFoundException
     */
    public function normalize(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return 'Unknown';
        }

        $fragments = config('bots.fragments', []);
        if (Str::contains($userAgent, $fragments, ignoreCase: true)) {
            $sortedFragments = self::getSortedBotFragments();
            $matchingFragment = collect($sortedFragments)
                ->first(fn(string $fragment) => Str::contains($userAgent, $fragment, ignoreCase: true));
            if ($matchingFragment !== null) {
                return Str::ucfirst($matchingFragment);
            }
        }

        $parser = Parser::create();
        $result = $parser->parse($userAgent);

        $uaFamily = $result->ua->family ?? 'Unknown';
        $osFamily = $result->os->family ?? 'Unknown';
        $deviceFamily = $result->device->family ?? 'Unknown';

        return trim("{$uaFamily} on {$osFamily} ({$deviceFamily}) ");
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
