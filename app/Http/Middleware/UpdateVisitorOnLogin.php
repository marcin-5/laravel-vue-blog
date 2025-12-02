<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Services\FingerprintGenerator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class UpdateVisitorOnLogin
{
    public function __construct(
        private FingerprintGenerator $fingerprintGenerator,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (!$user) {
            return $response;
        }

        // Make sure we are not doing this repeatedly
        if ($request->session()->get('page_views_synced_for_user')) {
            return $response;
        }

        $visitorId = (string)$request->cookie('visitor_id', '');
        $fingerprint = $this->fingerprintGenerator->generate($request);

        PageView::query()
            ->whereNull('user_id')
            ->when($visitorId !== '', function ($q) use ($visitorId) {
                $q->orWhere('visitor_id', $visitorId);
            })
            ->when($fingerprint !== null, function ($q) use ($fingerprint) {
                $q->orWhere('fingerprint', $fingerprint);
            })
            ->update(['user_id' => $user->getAuthIdentifier()]);

        $request->session()->put('page_views_synced_for_user', true);

        return $response;
    }
}
