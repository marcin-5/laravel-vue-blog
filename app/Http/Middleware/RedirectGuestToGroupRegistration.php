<?php

namespace App\Http\Middleware;

use App\Models\Group;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectGuestToGroupRegistration
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');

        if ($request->user() === null && $group instanceof Group && $group->allow_registration) {
            return redirect()->guest(route('group.register.create', $group));
        }

        return $next($request);
    }
}
