<?php

namespace App\Services;

use App\Jobs\StoreBotView;
use App\Jobs\StorePageView;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
     */
    public function track(Model $viewable, Request $request): void
    {
        if ($this->botDetector->isBot($request)) {
            StoreBotView::dispatch([
                'viewable_type' => $viewable->getMorphClass(),
                'viewable_id' => $viewable->getKey(),
                'user_agent' => (string)$request->header('User-Agent', ''),
            ]);

            return;
        }

        $visitorId = $request->cookie('visitor_id');
        $isNewVisitor = (bool)$request->attributes->get('visitor_id_is_new', true);

        // If client sent X-Visitor-Id header, they are likely a real user with LocalStorage support
        $headerVisitorId = $request->header('X-Visitor-Id');
        if ($headerVisitorId && $isNewVisitor) {
            $isNewVisitor = false;
            $visitorId = $headerVisitorId;
            // Update request for consistency
            $request->cookies->set('visitor_id', $visitorId);
        }

        // Check for a pending visit from this IP/UA/Fingerprint (for returning visitors)
        if (!$isNewVisitor) {
            $this->processPendingVisit($viewable, $request);
        }

        $this->recordView($viewable, $request);
    }

    private function processPendingVisit(Model $viewable, Request $request): void
    {
        $key = $this->getPendingVisitKey($viewable, $request);
        $pending = $this->cache->get($key);

        if ($pending) {
            $this->cache->forget($key);

            $visitorId = $request->cookie('visitor_id');

            StorePageView::dispatch([
                ...$pending,
                'visitor_id' => $visitorId,
                'user_id' => $this->auth->user()?->getAuthIdentifier(),
                'fingerprint' => $this->fingerprintGenerator->generate($request),
            ]);
        }
    }

    private function getPendingVisitKey(Model $viewable, Request $request): string
    {
        $fingerprint = $this->fingerprintGenerator->generate($request);

        return sprintf(
            'pending_visit:%s:%s:%s',
            $viewable->getMorphClass(),
            $viewable->getKey(),
            $fingerprint ?? hash('sha256', $request->ip() . $request->header('User-Agent')),
        );
    }

    private function recordView(Model $viewable, Request $request): void
    {
        $user = $this->auth->user();
        $userId = $user?->getAuthIdentifier();

        $visitorId = $request->cookie('visitor_id');
        $sessionId = $request->session()->getId();
        $fingerprint = $this->fingerprintGenerator->generate($request);

        $blockTtl = (int)config('blog.page_view_block_seconds', 3600);

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
            'user_agent' => (string)$request->header('User-Agent', ''),
            'fingerprint' => $fingerprint,
        ]);
    }

    private function storePendingVisit(Model $viewable, Request $request): void
    {
        $key = $this->getPendingVisitKey($viewable, $request);

        $this->cache->put($key, [
            'viewable_type' => $viewable->getMorphClass(),
            'viewable_id' => $viewable->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => (string)$request->header('User-Agent', ''),
            'session_id' => $request->session()->getId(),
        ], 600); // 10 minutes
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
