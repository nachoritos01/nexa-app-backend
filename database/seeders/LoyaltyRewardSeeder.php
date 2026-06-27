<?php

namespace Database\Seeders;

use App\Models\LoyaltyReward;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LoyaltyRewardSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            ['name' => '5% discount', 'type' => 'discount_percent', 'points_cost' => 50, 'value' => 5.00, 'icon' => 'tag', 'sort_order' => 1],
            ['name' => '10% discount', 'type' => 'discount_percent', 'points_cost' => 150, 'value' => 10.00, 'icon' => 'tag', 'sort_order' => 2],
            ['name' => '1 free month', 'type' => 'free_month', 'points_cost' => 200, 'value' => null, 'icon' => 'calendar', 'sort_order' => 3],
            ['name' => 'Storage upgrade (+10 GB)', 'type' => 'storage_upgrade', 'points_cost' => 300, 'value' => 10.00, 'icon' => 'cloud-arrow-up', 'sort_order' => 4],
            ['name' => '15% discount', 'type' => 'discount_percent', 'points_cost' => 500, 'value' => 15.00, 'icon' => 'tag', 'min_tier' => 1, 'sort_order' => 5],
            ['name' => 'Premium feature (30 days)', 'type' => 'feature_unlock', 'points_cost' => 500, 'value' => null, 'icon' => 'lock-open', 'min_tier' => 1, 'sort_order' => 6],
            ['name' => 'Plan upgrade (1 month)', 'type' => 'plan_upgrade', 'points_cost' => 750, 'value' => null, 'icon' => 'arrow-trending-up', 'min_tier' => 2, 'sort_order' => 7],
            ['name' => '20% discount', 'type' => 'discount_percent', 'points_cost' => 1000, 'value' => 20.00, 'icon' => 'fire', 'min_tier' => 2, 'sort_order' => 8],
            ['name' => '3 free months', 'type' => 'free_months', 'points_cost' => 1500, 'value' => null, 'icon' => 'sparkles', 'min_tier' => 3, 'sort_order' => 9],
        ];

        Tenant::all()->each(function (Tenant $tenant) use ($rewards) {
            foreach ($rewards as $reward) {
                LoyaltyReward::withoutGlobalScopes()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $reward['name']],
                    array_merge($reward, ['tenant_id' => $tenant->id]),
                );
            }
        });

        $this->command->info('Loyalty rewards seeded successfully.');
    }
}
