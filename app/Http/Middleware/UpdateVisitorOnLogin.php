<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Models\VisitorLink;
use App\Services\FingerprintGenerator;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $userId = $user->getAuthIdentifier();

        // Persist mapping visitor_id -> user_id for future requests
        if ($visitorId !== '') {
            VisitorLink::query()->updateOrCreate(
                ['visitor_id' => $visitorId],
                ['user_id' => $userId],
            );
        }

        $query = PageView::query()
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
            });

        // 1. Prune anonymous duplicates that would conflict with existing records of this user.
        // We do this because unique constraint on (user_id, viewable_type, viewable_id)
        // prevents us from simply updating all records.
        (clone $query)
            ->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('page_views as pv2')
                    ->whereColumn('pv2.viewable_type', 'page_views.viewable_type')
                    ->whereColumn('pv2.viewable_id', 'page_views.viewable_id')
                    ->where('pv2.user_id', $userId);
            })
            ->delete();

        // 2. Update remaining anonymous records to belong to the user.
        $query->update(['user_id' => $userId]);

        $request->session()->put('page_views_synced_for_user', true);
    }
}
