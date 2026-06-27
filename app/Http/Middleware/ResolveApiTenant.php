<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveApiTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        $token = $user->currentAccessToken();
        $tenantId = null;

        // 1. Token-scoped tenant (web dashboard tokens)
        if ($token instanceof PersonalAccessToken && $token->tenant_id) {
            $tenantId = $token->tenant_id;
        }

        // 2. X-Tenant-ID header fallback (mobile tokens without tenant_id)
        if (! $tenantId && $request->hasHeader('X-Tenant-ID')) {
            $headerTenantId = (int) $request->header('X-Tenant-ID');

            // Verify user belongs to this tenant
            if ($headerTenantId && $user->tenants()->where('tenants.id', $headerTenantId)->exists()) {
                $tenantId = $headerTenantId;
            }
        }

        if (! $tenantId) {
            return response()->json([
                'error' => 'Token is not associated with a tenant.',
                'code' => 'INVALID_TOKEN',
            ], 403);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant not found.',
                'code' => 'TENANT_NOT_FOUND',
            ], 403);
        }

        if (! $tenant->is_active || $tenant->isSuspended()) {
            return response()->json([
                'error' => 'Tenant is inactive or suspended.',
                'code' => 'TENANT_INACTIVE',
            ], 403);
        }

        app()->instance('currentTenant', $tenant);

        return $next($request);
    }
}
