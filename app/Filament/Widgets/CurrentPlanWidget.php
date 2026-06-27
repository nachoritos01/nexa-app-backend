<?php

namespace App\Filament\Widgets;

use App\Enums\PlanType;
use Filament\Widgets\Widget;

class CurrentPlanWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected static string $view = 'filament.widgets.current-plan';

    public static function canView(): bool
    {
        if (! hasModule('payments')) {
            return false;
        }

        $tenant = currentTenant();

        if (! $tenant) {
            return false;
        }

        return $tenant->isSubscribed() || $tenant->isOnTrial() || $tenant->isTrialExpired();
    }

    public function getTenant(): ?\App\Models\Tenant
    {
        return currentTenant();
    }

    public function getPlanData(): array
    {
        $tenant = $this->getTenant();

        if (! $tenant) {
            return [];
        }

        $status = 'trial';
        if ($tenant->isSubscribed()) {
            $status = 'active';
        } elseif ($tenant->isTrialExpired()) {
            $status = 'expired';
        }

        return [
            'plan' => $tenant->planLabel(),
            'plan_key' => $tenant->plan,
            'status' => $status,
            'days_remaining' => $tenant->trialDaysRemaining(),
            'show_upgrade' => ! PlanType::from($tenant->plan)->isHigherThan(PlanType::Growth),
        ];
    }
}
