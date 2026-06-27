<?php

namespace Database\Seeders;

use App\Enums\BillingEventType;
use App\Models\BillingEvent;
use App\Models\Tenant;
use App\Models\TenantPlugin;
use Illuminate\Database\Seeder;

class BillingEventSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Backfill subscription_started for subscribed tenants
            if ($tenant->subscribed_at) {
                BillingEvent::withoutGlobalScopes()->firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'type' => BillingEventType::SubscriptionStarted->value,
                    ],
                    [
                        'description' => 'Subscribed to ' . $tenant->planLabel() . ' plan',
                        'amount' => config("saas.plans.{$tenant->plan}.price_monthly"),
                        'metadata' => ['plan' => $tenant->plan, 'backfilled' => true],
                        'created_at' => $tenant->subscribed_at,
                        'updated_at' => $tenant->subscribed_at,
                    ]
                );
            }

            // Backfill plugin_activated for active paid plugins
            $paidPivots = TenantPlugin::where('tenant_id', $tenant->id)
                ->where('billing_type', 'paid')
                ->where('is_active', true)
                ->get();

            foreach ($paidPivots as $pivot) {
                $plugin = \App\Models\Plugin::find($pivot->plugin_id);

                if (! $plugin) {
                    continue;
                }

                BillingEvent::withoutGlobalScopes()->firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'type' => BillingEventType::PluginActivated->value,
                        'description' => "Activated {$plugin->name}",
                    ],
                    [
                        'amount' => $plugin->price_monthly,
                        'metadata' => ['plugin_slug' => $plugin->slug, 'backfilled' => true],
                        'created_at' => $pivot->activated_at ?? $pivot->created_at,
                        'updated_at' => $pivot->activated_at ?? $pivot->created_at,
                    ]
                );
            }
        }
    }
}
