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
        $response = $next($request);

        $visitorId = (string)$request->cookie('visitor_id', '');

        if ($visitorId === '') {
            $visitorId = (string)Str::uuid();

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
        }

        return $response;
    }
}
