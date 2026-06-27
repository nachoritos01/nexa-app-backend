<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PlanLimitsBanner extends Widget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.plan-limits-banner';

    public static function canView(): bool
    {
        if (! hasModule('payments')) {
            return false;
        }

        $tenant = currentTenant();

        if (! $tenant || $tenant->plan === 'pro') {
            return false;
        }

        $resources = ['orders', 'users', 'branches', 'products', 'customers'];

        foreach ($resources as $resource) {
            if ($tenant->isNearLimit($resource) || $tenant->isAtLimit($resource)) {
                return true;
            }
        }

        return false;
    }

    public function getWarnings(): array
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return [];
        }

        $warnings = [];
        $resources = [
            'orders' => 'orders this month',
            'users' => 'users',
            'branches' => 'locations',
            'products' => 'items',
            'customers' => 'customers',
        ];

        foreach ($resources as $resource => $label) {
            if ($tenant->isAtLimit($resource)) {
                $limits = $tenant->planLimits();
                $warnings[] = [
                    'type' => 'danger',
                    'message' => "You have reached the limit of {$label} ({$limits[$resource]}). Upgrade your plan to continue creating.",
                ];
            } elseif ($tenant->isNearLimit($resource)) {
                $percentage = $tenant->usagePercentage($resource);
                $warnings[] = [
                    'type' => 'warning',
                    'message' => "You are near the limit of {$label} ({$percentage}% used).",
                ];
            }
        }

        return $warnings;
    }
}
