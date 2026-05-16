<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\StoreAnonymousView;
use App\Jobs\StoreBotView;
use App\Jobs\StorePageView;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
     * @throws ClassMorphViolationException|InvalidArgumentException
     */
    public function track(Model $viewable, Request $request): void
    {
        if ($this->botDetector->isBot($request)) {
            StoreBotView::dispatch([
                'viewable_type' => $viewable->getMorphClass(),
                'viewable_id' => $viewable->getKey(),
                'user_agent' => (string) $request->header('User-Agent', ''),
            ]);

            return;
        }

        $consent = $request->cookie('cookie_consent');
        if (!$consent || $consent === 'rejected') {
            StoreAnonymousView::dispatch([
                'viewable_type' => $viewable->getMorphClass(),
                'viewable_id' => $viewable->getKey(),
                'user_agent' => (string) $request->header('User-Agent', ''),
            ]);

            return;
        }

        // Handle visitor ID from header if it's a new visitor (likely first request from SPA)
        if ($request->attributes->get('visitor_id_is_new', true)) {
            $headerVisitorId = $request->header('X-Visitor-Id');
            if ($headerVisitorId) {
                $request->cookies->set('visitor_id', $headerVisitorId);
            }
        }

        $this->recordView($viewable, $request);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function recordView(Model $viewable, Request $request): void
    {
        $user = $this->auth->user();
        $userId = $user?->getAuthIdentifier();

        $visitorId = $request->cookie('visitor_id');
        $sessionId = $request->session()->getId();
        $fingerprint = $this->fingerprintGenerator->generate($request);

        $blockTtl = (int) config('blog.page_view_block_seconds', 3600);

        // Blocking keys
        $baseKey = sprintf('page_view:block:%s:%d', $viewable->getMorphClass(), $viewable->getKey());

        // Choose a single identity to de-duplicate by (do not block other identities)
        // Priority: user -> visitor -> fingerprint
        $identityKey = null;
        if ($userId !== null) {
            $identityKey = $baseKey . ':user:' . $userId;
        } elseif ($visitorId !== null) {
            $identityKey = $baseKey . ':visitor:' . $visitorId;
        } elseif ($fingerprint !== null) {
            $identityKey = $baseKey . ':fingerprint:' . $fingerprint;
        }

        if ($identityKey !== null && $this->cache->has($identityKey)) {
            return;
        }

        if ($identityKey !== null) {
            $this->cache->put($identityKey, 1, $blockTtl);
        }

        // Dispatch the page view record to the queue
        StorePageView::dispatch([
            'user_id' => $userId,
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'viewable_type' => $viewable->getMorphClass(),
            'viewable_id' => $viewable->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->header('User-Agent', ''),
            'fingerprint' => $fingerprint,
        ]);
    }
}
