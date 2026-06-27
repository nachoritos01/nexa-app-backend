<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    /** Routes that suspended tenants can still access. */
    private const SUSPENSION_BYPASS_ROUTES = [
        'suspended',
        'billing.checkout',
        'billing.portal',
        'stripe.webhook',
        'filament.admin.auth.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        // Try session first
        $tenantId = session('tenant_id');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);

            // Validate user still belongs to this tenant
            if ($tenant && $user->tenants()->where('tenant_id', $tenant->id)->exists()) {
                $this->bindTenant($tenant, $user);

                return $this->handleSuspension($request, $next, $tenant);
            }

            // Invalid session tenant — clear it
            session()->forget('tenant_id');
        }

        // Auto-select if user has exactly 1 tenant (active)
        $userTenants = $user->tenants()->where('is_active', true)->get();

        if ($userTenants->count() === 1) {
            /** @var Tenant $tenant */
            $tenant = $userTenants->first();
            session(['tenant_id' => $tenant->id]);
            $this->bindTenant($tenant, $user);

            return $this->handleSuspension($request, $next, $tenant);
        }

        if ($userTenants->count() === 0) {
            // Check if user has suspended tenants
            $suspendedTenant = $user->tenants()->where('is_active', false)->first();

            if ($suspendedTenant) {
                $this->bindTenant($suspendedTenant, $user);
                session(['tenant_id' => $suspendedTenant->id]);

                return $this->handleSuspension($request, $next, $suspendedTenant);
            }

            // No tenants at all — allow through (e.g. onboarding)
            return $next($request);
        }

        // Multiple tenants — for now, select first one (tenant switcher TBD)
        /** @var Tenant $tenant */
        $tenant = $userTenants->first();
        session(['tenant_id' => $tenant->id]);
        $this->bindTenant($tenant, $user);

        return $this->handleSuspension($request, $next, $tenant);
    }

    private function handleSuspension(Request $request, Closure $next, Tenant $tenant): Response
    {
        if ($tenant->isSuspended() && ! $this->isBypassRoute($request)) {
            return redirect()->route('suspended');
        }

        return $next($request);
    }

    private function isBypassRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return false;
        }

        return in_array($routeName, self::SUSPENSION_BYPASS_ROUTES);
    }

    private function bindTenant(Tenant $tenant, User $user): void
    {
        app()->instance('currentTenant', $tenant);

        // Sync Spatie role from tenant_user pivot
        $pivotRole = $user->tenants()
            ->where('tenant_id', $tenant->id)
            ->first()
            ?->pivot
            ?->role;

        if ($pivotRole) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            $user->syncRoles([$pivotRole]);
        }
    }
}
