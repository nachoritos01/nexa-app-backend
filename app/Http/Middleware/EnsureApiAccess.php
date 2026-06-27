<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = currentTenant();

        if (! $tenant || $tenant->plan !== 'pro') {
            return response()->json([
                'error' => 'API access requires the Pro plan. Please upgrade at your billing settings.',
                'code' => 'PLAN_UPGRADE_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}
