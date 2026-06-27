<?php

namespace App\Services;

use App\Enums\PlanType;
use App\Events\PlanChanged;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

class BillingService
{
    public function swapPlan(Tenant $tenant, string $newPlan, string $priceId): ?string
    {
        $isDowngrade = PlanType::from($newPlan)->order() < PlanType::from($tenant->plan)->order();

        if ($isDowngrade) {
            $error = $this->validateDowngrade($tenant, $newPlan);
            if ($error) {
                return $error;
            }
        }

        $previousPlan = $tenant->plan;

        $tenant->subscription('default')->swap($priceId);

        $tenant->update(['plan' => $newPlan]);
        $tenant->clearUsageCache();

        PlanChanged::dispatch($tenant, $previousPlan, $newPlan);

        return null;
    }

    public function validateDowngrade(Tenant $tenant, string $newPlan): ?string
    {
        $newLimits = config("saas.plans.{$newPlan}", []);
        $usage = $tenant->usageCounts();

        $checks = [
            'users' => ['max_users', 'users'],
            'locations' => ['max_locations', 'locations'],
            'items' => ['max_items', 'items'],
            'customers' => ['max_customers', 'customers'],
        ];

        $conflicts = [];

        foreach ($checks as $resource => [$limitKey, $label]) {
            $max = $newLimits[$limitKey] ?? null;
            if ($max !== null && ($usage[$resource] ?? 0) > $max) {
                $conflicts[] = "{$usage[$resource]}/{$max} {$label}";
            }
        }

        if (empty($conflicts)) {
            return null;
        }

        return 'Cannot downgrade to this plan. You exceed limits in: ' . implode(', ', $conflicts) . '.';
    }
}
