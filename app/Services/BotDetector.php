<?php

declare(strict_types=1);

namespace App\Services;

use DeviceDetector\DeviceDetector;
use Exception;
use Illuminate\Http\Request;

readonly class BotDetector
{
    /**
     * Return detector-reported bot name, or null when not a bot.
     * @throws Exception
     */
    public function getBotName(Request|string|null $requestOrUa): ?string
    {
        $ua = $this->extractUserAgent($requestOrUa);
        if ($ua === '') {
            return null;
        }

        $dd = new DeviceDetector($ua);
        $dd->parse();

        if (!$dd->isBot()) {
            return null;
        }

        $info = $dd->getBot();
        $name = is_array($info) ? ($info['name'] ?? null) : null;

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
     * Quick bot check using Matomo DeviceDetector signatures.
     * @throws Exception
     */
    public function isBot(Request|string|null $requestOrUa): bool
    {
        $ua = $this->extractUserAgent($requestOrUa);
        if ($ua === '') {
            return false;
        }

        $dd = new DeviceDetector($ua);
        $dd->parse();

        return $dd->isBot();
    }
}
