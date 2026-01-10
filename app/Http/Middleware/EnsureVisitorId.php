<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureVisitorId
{
    public function handle(Request $request, Closure $next): Response
    {
        // Try to read the decrypted cookie value first
        $visitorId = (string)$request->cookie('visitor_id', '');
        $isNewVisitor = false;

        // If missing, generate and ensure both the current request and response carry it
        if ($visitorId === '') {
            $visitorId = (string)Str::uuid();
            $isNewVisitor = true;

            // Make it available to subsequent middleware/handlers in this request
            $request->cookies->set('visitor_id', $visitorId);
        }

        // Flag the request so PageViewTracker knows if this is a "fresh" cookie
        $request->attributes->set('visitor_id_is_new', $isNewVisitor);

        $response = $next($request);

        // Always ensure the persistent cookie is set (refresh max-age)
        $response->headers->setCookie(
            cookie(
                'visitor_id',
                $visitorId,
                60 * 24 * 365, // 1 year
                null,
                null,
                false,
                true,
                false,
                'Strict',
            ),
        );

        return $response;
    }
}
