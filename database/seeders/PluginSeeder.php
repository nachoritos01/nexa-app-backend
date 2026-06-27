<?php

namespace Database\Seeders;

use App\Models\Plugin;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PluginSeeder extends Seeder
{
    public function run(): void
    {
        $plugins = [
            [
                'slug' => 'payments',
                'name' => 'Payment Processing',
                'description' => 'Accept payments via Stripe, manage invoices, and track billing.',
                'icon' => 'heroicon-o-credit-card',
                'category' => 'billing',
                'is_free' => true,
                'included_in_plans' => ['starter', 'growth', 'pro'],
                'required_modules' => ['payments'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'customer_portal',
                'name' => 'Customer Portal',
                'description' => 'Self-service portal for customers to view orders and track status.',
                'icon' => 'heroicon-o-user-group',
                'category' => 'engagement',
                'is_free' => false,
                'price_monthly' => 499,
                'stripe_price_id' => 'price_customer_portal_monthly',
                'included_in_plans' => ['growth', 'pro'],
                'required_modules' => ['customer_portal'],
                'sort_order' => 20,
            ],
            [
                'slug' => 'locations',
                'name' => 'Multiple Locations',
                'description' => 'Manage multiple business locations with separate inventories.',
                'icon' => 'heroicon-o-map-pin',
                'category' => 'operations',
                'is_free' => true,
                'included_in_plans' => ['starter', 'growth', 'pro'],
                'required_modules' => ['locations'],
                'sort_order' => 30,
            ],
            [
                'slug' => 'api',
                'name' => 'API Access',
                'description' => 'RESTful API access for integrations and automation.',
                'icon' => 'heroicon-o-code-bracket',
                'category' => 'developer',
                'is_free' => true,
                'included_in_plans' => ['pro'],
                'required_modules' => ['api'],
                'sort_order' => 40,
            ],
            [
                'slug' => 'exports',
                'name' => 'CSV Exports',
                'description' => 'Export orders, customers, and reports to CSV files.',
                'icon' => 'heroicon-o-arrow-down-tray',
                'category' => 'reporting',
                'is_free' => true,
                'included_in_plans' => ['growth', 'pro'],
                'required_modules' => ['exports'],
                'sort_order' => 50,
            ],
            [
                'slug' => 'loyalty',
                'name' => 'Loyalty Program',
                'description' => 'Points, tiers, and redeemable rewards system to boost customer retention.',
                'icon' => 'heroicon-o-star',
                'category' => 'engagement',
                'is_free' => false,
                'price_monthly' => 999,
                'stripe_price_id' => 'price_loyalty_monthly',
                'included_in_plans' => ['growth', 'pro'],
                'required_modules' => ['loyalty', 'customer_portal'],
                'sort_order' => 15,
            ],
            [
                'slug' => 'advanced_analytics',
                'name' => 'Advanced Analytics',
                'description' => 'Detailed dashboards with cohort analysis, churn prediction, and revenue forecasting.',
                'icon' => 'heroicon-o-chart-bar-square',
                'category' => 'reporting',
                'is_free' => false,
                'price_monthly' => 1999,
                'stripe_price_id' => 'price_advanced_analytics_monthly',
                'included_in_plans' => [],
                'required_modules' => [],
                'sort_order' => 60,
            ],
            [
                'slug' => 'white_label',
                'name' => 'White Label',
                'description' => 'Remove branding, use custom domain, and customize emails with your logo.',
                'icon' => 'heroicon-o-paint-brush',
                'category' => 'general',
                'is_free' => false,
                'price_monthly' => 4999,
                'stripe_price_id' => 'price_white_label_monthly',
                'included_in_plans' => [],
                'required_modules' => [],
                'sort_order' => 70,
            ],
        ];

        foreach ($plugins as $data) {
            Plugin::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }

        // Auto-activate included plugins for existing tenants
        $allPlugins = Plugin::all();

        Tenant::all()->each(function (Tenant $tenant) use ($allPlugins) {
            foreach ($allPlugins as $plugin) {
                if ($plugin->isIncludedInPlan($tenant->plan)) {
                    // Only create if not already exists
                    $exists = $tenant->plugins()->where('plugin_id', $plugin->id)->exists();

                    if (! $exists) {
                        $tenant->activatePlugin($plugin);
                    }
                }
            }
        });

        $this->command->info('Plugins seeded successfully.');
    }
}
