<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Block access to a route "area" (e.g. 'products', 'finance', 'users')
     * unless the logged-in user's role is allowed into it.
     */
    public function handle(Request $request, Closure $next, string $area): Response
    {
        if (! $request->user() || ! $request->user()->canAccess($area)) {
            abort(403, 'You do not have permission to access this section.');
        }

        return $next($request);
    }
}
