<?php

namespace App\Services;

use Illuminate\Http\Request;

use function hash;
use function implode;
use function sprintf;
use function str_contains;

/**
 * Generates browser fingerprints for tracking unique visitors.
 * Uses IP bucket normalization to handle dynamic IPs within the same network.
 */
readonly class FingerprintGenerator
{
    /**
     * Generate a fingerprint hash from the request.
     */
    public function generate(Request $request): ?string
    {
        $ip = $request->ip();
        $ua = (string)$request->header('User-Agent', '');
        $acceptLang = (string)$request->header('Accept-Language', '');

        if ($ip === null && $ua === '' && $acceptLang === '') {
            return null;
        }

        $ipBucket = $this->normalizeIpToBucket($ip);

        return hash('sha256', implode('|', [
            $ipBucket,
            $ua,
            $acceptLang,
        ]));
    }

    /**
     * Brings the IP to a more stable "bucket":
     * - IPv4: /24 (e.g., 192.168.1.123 → 192.168.1.0)
     * - IPv6: first 4 hextets (example)
     */
    public function normalizeIpToBucket(?string $ip): string
    {
        if ($ip === null || $ip === '') {
            return 'ip:none';
        }

        // Simple detection of IPv4/IPv6 by format
        if (str_contains($ip, ':')) {
            // IPv6 – take the first 4 "hextets" as an approximate prefix
            $parts = explode(':', $ip);
            $bucket = implode(':', array_slice($parts, 0, 4));

            return 'ipv6:' . $bucket;
        }

        // IPv4 – collapsing to /24
        $octets = explode('.', $ip);
        if (count($octets) !== 4) {
            return 'ipv4:invalid';
        }

        // 192.168.1.123 -> 192.168.1.0
        [$o1, $o2, $o3] = $octets;

        return sprintf('ipv4:%s.%s.%s.0', $o1, $o2, $o3);
    }
}
