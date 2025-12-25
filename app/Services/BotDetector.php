<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

readonly class BotDetector
{
    /**
     * Determine if the current request is from a bot.
     *
     * @param Request $request
     * @return bool
     */
    public function isBot(Request $request): bool
    {
        $userAgent = (string)$request->header('User-Agent', '');

        if ($userAgent === '') {
            return false;
        }

        $fragments = config('bots.fragments', []);

        return Str::contains($userAgent, $fragments, ignoreCase: true);
    }
}
