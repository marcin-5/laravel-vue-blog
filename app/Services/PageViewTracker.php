<?php

namespace App\Services;

use App\Jobs\StorePageView;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

use function hash;
use function implode;
use function sprintf;

/**
 * Tracks page view events and prevents duplicate tracking using caching mechanisms,
 * while also storing unique fingerprints for users and visitors.
 */
readonly class PageViewTracker
{
    public function __construct(
        private Guard $auth,
        private CacheRepository $cache,
    ) {
    }

    /**
     * @throws ClassMorphViolationException
     * @throws InvalidArgumentException
     */
    public function track(Model $viewable, Request $request): void
    {
        $user = $this->auth->user();
        $userId = $user?->getAuthIdentifier();

        $visitorId = $this->resolveVisitorId($request);
        $sessionId = $request->session()->getId();
        $fingerprint = $this->makeFingerprint($request);

        $blockTtl = (int)config('blog.page_view_block_seconds', 3600);

        // Blocking keys
        $baseKey = sprintf('page_view:block:%s:%d', $viewable->getMorphClass(), $viewable->getKey());

        $keysToCheck = [];

        if ($fingerprint !== null) {
            $keysToCheck[] = $baseKey . ':fingerprint:' . $fingerprint;
        }

        if ($userId !== null) {
            $keysToCheck[] = $baseKey . ':user:' . $userId;
        } elseif ($visitorId !== null) {
            $keysToCheck[] = $baseKey . ':visitor:' . $visitorId;
        }

        // Check if any of the keys already exist (avoid duplicates)
        if (array_any($keysToCheck, fn($key) => $this->cache->has($key))) {
            return; // already counted in this time window
        }

        // Set blocking keys in Redis
        foreach ($keysToCheck as $key) {
            $this->cache->put($key, 1, $blockTtl);
        }

        // Dispatch the page view record to the queue
        StorePageView::dispatch([
            'user_id' => $userId,
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'viewable_type' => $viewable->getMorphClass(),
            'viewable_id' => $viewable->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => (string)$request->header('User-Agent', ''),
            'fingerprint' => $fingerprint,
        ]);
    }

    private function resolveVisitorId(Request $request): ?string
    {
        // If already exists in cookie, use it
        $current = (string)$request->cookie('visitor_id', '');
        if ($current !== '') {
            return $current;
        }

        // Generate a new one (only for logic purposes; middleware handles cookie writing)
        return (string)Str::uuid();
    }

    private function makeFingerprint(Request $request): ?string
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
    private function normalizeIpToBucket(?string $ip): string
    {
        if ($ip === null || $ip === '') {
            return 'ip:none';
        }

        // Simple detection of IPv4/IPv6 by format
        if (str_contains($ip, ':')) {
            // IPv6 – we take the first 4 "hextets" as an approximate prefix
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
