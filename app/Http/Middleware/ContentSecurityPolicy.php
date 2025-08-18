<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (app()->environment('local')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self' http: https: data: blob: 'unsafe-inline'; connect-src 'self' ws://localhost:5173; script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173",
            );
        } else {
            // production CSP
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self' http: https: data: blob: 'unsafe-inline';",
            );
        }


        return $response;
    }
}
