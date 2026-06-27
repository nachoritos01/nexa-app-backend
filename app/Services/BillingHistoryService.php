<?php

namespace App\Services;

use App\Enums\BillingEventType;
use App\Models\BillingEvent;
use App\Models\Plugin;
use App\Models\Tenant;

class BillingHistoryService
{
    public function recordSubscriptionStarted(Tenant $tenant, string $plan): void
    {
        $planConfig = config("saas.plans.{$plan}", []);
        $label = $planConfig['label'] ?? ucfirst($plan);
        $price = $planConfig['price_monthly'] ?? null;

        BillingEvent::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'type' => BillingEventType::SubscriptionStarted,
            'description' => "Subscribed to {$label} plan",
            'amount' => $price,
            'metadata' => ['plan' => $plan],
        ]);
    }

    public function recordPlanChanged(Tenant $tenant, string $from, string $to): void
    {
        $fromLabel = config("saas.plans.{$from}.label", ucfirst($from));
        $toLabel = config("saas.plans.{$to}.label", ucfirst($to));
        $toPrice = config("saas.plans.{$to}.price_monthly");

        BillingEvent::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'type' => BillingEventType::PlanChanged,
            'description' => "Changed plan from {$fromLabel} to {$toLabel}",
            'amount' => $toPrice,
            'metadata' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function recordPluginActivated(Tenant $tenant, Plugin $plugin): void
    {
        BillingEvent::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'type' => BillingEventType::PluginActivated,
            'description' => "Activated {$plugin->name}",
            'amount' => $plugin->is_free ? null : $plugin->price_monthly,
            'metadata' => ['plugin_slug' => $plugin->slug],
        ]);
    }

    public function recordPluginDeactivated(Tenant $tenant, Plugin $plugin): void
    {
        BillingEvent::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'type' => BillingEventType::PluginDeactivated,
            'description' => "Deactivated {$plugin->name}",
            'amount' => null,
            'metadata' => ['plugin_slug' => $plugin->slug],
        ]);
    }

    public function recordSubscriptionCancelled(Tenant $tenant): void
    {
        $label = $tenant->planLabel();

        BillingEvent::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'type' => BillingEventType::SubscriptionCancelled,
            'description' => "Cancelled {$label} plan subscription",
            'amount' => null,
            'metadata' => ['plan' => $tenant->plan],
        ]);
    }
}
