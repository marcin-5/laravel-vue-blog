<?php

namespace App\Services;

use App\Models\VisitorLink;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class IdentityResolver
{
    public function __construct(public Guard $auth)
    {
    }

    public function resolvedUserId(Request $request): ?int
    {
        return $this->resolveIdentity($request)['user_id'];
    }

    /**
     * Resolve the effective identity for the current request.
     * If a logged-in user exists, use user_id.
     * Else, if there is a mapping from visitor_id to a user, prefer that user_id.
     * Otherwise, return the raw visitor_id from cookie.
     *
     * @return array{user_id: int|null, visitor_id: string|null}
     */
    public function resolveIdentity(Request $request): array
    {
        $user = $this->auth->user();
        if ($user !== null) {
            return [
                'user_id' => (int)$user->getAuthIdentifier(),
                'visitor_id' => (string)$request->cookie('visitor_id', '') ?: null
            ];
        }

        $visitorId = (string)$request->cookie('visitor_id', '') ?: null;
        if ($visitorId !== null) {
            $link = VisitorLink::query()->where('visitor_id', $visitorId)->first();
            if ($link !== null) {
                return ['user_id' => (int)$link->user_id, 'visitor_id' => $visitorId];
            }
        }

        return ['user_id' => null, 'visitor_id' => $visitorId];
    }

    public function resolvedVisitorId(Request $request): ?string
    {
        return $this->resolveIdentity($request)['visitor_id'];
    }
}
