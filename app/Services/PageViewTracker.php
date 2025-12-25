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
        private FingerprintGenerator $fingerprintGenerator,
        private BotDetector $botDetector,
    ) {
    }

    /**
     * @throws ClassMorphViolationException
     * @throws InvalidArgumentException
     */
    public function track(Model $viewable, Request $request): void
    {
        if ($this->botDetector->isBot($request)) {
            return;
        }

        $user = $this->auth->user();
        $userId = $user?->getAuthIdentifier();

        $visitorId = $this->resolveVisitorId($request);
        $sessionId = $request->session()->getId();
        $fingerprint = $this->fingerprintGenerator->generate($request);

        $blockTtl = (int)config('blog.page_view_block_seconds', 3600);

        // Blocking keys
        $baseKey = sprintf('page_view:block:%s:%d', $viewable->getMorphClass(), $viewable->getKey());

        $keysToCheck = [];

        if ($fingerprint !== null) {
            $keysToCheck[] = $baseKey . ':fingerprint:' . $fingerprint;
        }

        // Always add both user and visitor keys when available to ensure
        // blocking works correctly across login/logout transitions
        if ($userId !== null) {
            $keysToCheck[] = $baseKey . ':user:' . $userId;
        }

        if ($visitorId !== null) {
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
}
