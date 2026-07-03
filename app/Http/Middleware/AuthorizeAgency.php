<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorizes access to the agency API by spatie permission, mapped from the HTTP
 * method: safe methods (GET/HEAD) require `agency.view`, writes require `agency.manage`.
 *
 * Uses $request->user()->can(), which resolves against the active (sanctum) guard,
 * instead of the spatie `permission:` middleware, which keys off the default guard
 * and would 403 authorized token users.
 */
class AuthorizeAgency
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $needed = $request->isMethodSafe() ? 'agency.view' : 'agency.manage';

        if (! $user || ! $user->can($needed)) {
            return response()->json([
                'error' => 'No autorizado.',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        return $next($request);
    }
}
