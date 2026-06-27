<?php

namespace App\Services;

use App\Models\LoyaltyReward;
use App\Models\Plugin;
use App\Models\Tenant;

class TenantSeedService
{
    /**
     * Seed initial data for a newly created tenant.
     *
     * Override this method to add default items, locations, or other
     * seed data that every new tenant should start with.
     */
    public function seedForTenant(Tenant $tenant): void
    {
        $previousTenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        app()->instance('currentTenant', $tenant);

        $this->activateIncludedPlugins($tenant);
        $this->seedLoyaltyRewards($tenant);

        if ($previousTenant) {
            app()->instance('currentTenant', $previousTenant);
        } else {
            app()->forgetInstance('currentTenant');
        }
    }

    /**
     * Seed default loyalty rewards for the tenant.
     */
    private function seedLoyaltyRewards(Tenant $tenant): void
    {
        if (LoyaltyReward::withoutGlobalScopes()->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $rewards = [
            ['name' => '5% discount', 'type' => 'discount_percent', 'points_cost' => 50, 'value' => 5.00, 'sort_order' => 1],
            ['name' => '10% discount', 'type' => 'discount_percent', 'points_cost' => 150, 'value' => 10.00, 'sort_order' => 2],
            ['name' => '1 free month', 'type' => 'free_month', 'points_cost' => 200, 'value' => null, 'sort_order' => 3],
            ['name' => 'Storage upgrade (+10 GB)', 'type' => 'storage_upgrade', 'points_cost' => 300, 'value' => 10.00, 'sort_order' => 4],
            ['name' => '15% discount', 'type' => 'discount_percent', 'points_cost' => 500, 'value' => 15.00, 'min_tier' => 1, 'sort_order' => 5],
            ['name' => 'Premium feature (30 days)', 'type' => 'feature_unlock', 'points_cost' => 500, 'value' => null, 'min_tier' => 1, 'sort_order' => 6],
            ['name' => 'Plan upgrade (1 month)', 'type' => 'plan_upgrade', 'points_cost' => 750, 'value' => null, 'min_tier' => 2, 'sort_order' => 7],
            ['name' => '20% discount', 'type' => 'discount_percent', 'points_cost' => 1000, 'value' => 20.00, 'min_tier' => 2, 'sort_order' => 8],
            ['name' => '3 free months', 'type' => 'free_months', 'points_cost' => 1500, 'value' => null, 'min_tier' => 3, 'sort_order' => 9],
        ];

        foreach ($rewards as $reward) {
            LoyaltyReward::withoutGlobalScopes()->create(array_merge($reward, ['tenant_id' => $tenant->id]));
        }
    }

    /**
     * Activate all free/included plugins for the tenant's plan.
     */
    private function activateIncludedPlugins(Tenant $tenant): void
    {
        $plugins = Plugin::where('is_active', true)->get();

        foreach ($plugins as $plugin) {
            if ($plugin->isIncludedInPlan($tenant->plan)) {
                $exists = $tenant->plugins()->where('plugin_id', $plugin->id)->exists();

                if (! $exists) {
                    $tenant->activatePlugin($plugin);
                }
            }
        }
    }
}
