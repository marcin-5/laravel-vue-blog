<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Services\FingerprintGenerator;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
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

        if ($user && !$request->session()->get('page_views_synced_for_user')) {
            $this->syncPageViews($request, $user);
        }

        return $response;
    }

    private function syncPageViews(Request $request, Authenticatable $user): void
    {
        $visitorId = (string)$request->cookie('visitor_id', '');
        $fingerprint = $this->fingerprintGenerator->generate($request);

        // Ensure we identify the anonymous user by at least one method
        // before attempting to attach their history.
        if ($visitorId === '' && $fingerprint === null) {
            return;
        }

        PageView::query()
            ->whereNull('user_id')
            ->where(function (Builder $query) use ($visitorId, $fingerprint) {
                $has = false;
                if ($visitorId !== '') {
                    $query->where('visitor_id', $visitorId);
                    $has = true;
                }
                if ($fingerprint !== null) {
                    $has ? $query->orWhere('fingerprint', $fingerprint)
                        : $query->where('fingerprint', $fingerprint);
                }
            })
            ->update(['user_id' => $user->getAuthIdentifier()]);

        $request->session()->put('page_views_synced_for_user', true);
    }
}
