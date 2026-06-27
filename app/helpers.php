<?php

use App\Models\Tenant;

if (! function_exists('currentTenant')) {
    function currentTenant(): ?Tenant
    {
        if (app()->bound('currentTenant')) {
            return app('currentTenant');
        }

        return null;
    }
}

if (! function_exists('hasModule')) {
    /**
     * Check if an optional module is enabled.
     *
     * Layer 1: Global kill switch (config/modules.php)
     * Layer 2: Per-tenant plugin activation (tenant_plugins table)
     *
     * Results are memoized per-request. Pass $module = null to clear cache.
     */
    function hasModule(?string $module = null): bool
    {
        static $cache = [];

        // Clear cache when called with null
        if ($module === null) {
            $cache = [];

            return false;
        }

        // Layer 1: Global kill switch
        if (! config("modules.{$module}", false)) {
            return false;
        }

        // Layer 2: Per-tenant plugin check
        $tenant = currentTenant();

        if (! $tenant) {
            return true; // No tenant context = global only (routes, commands)
        }

        $cacheKey = "{$tenant->id}:{$module}";

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $plugin = \App\Models\Plugin::where('slug', $module)->first();

        if (! $plugin) {
            return $cache[$cacheKey] = true; // No plugin record = backward compatible
        }

        return $cache[$cacheKey] = $tenant->hasPlugin($module);
    }
}
