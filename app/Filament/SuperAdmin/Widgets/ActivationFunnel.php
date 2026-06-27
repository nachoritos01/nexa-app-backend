<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Tenant;
use Filament\Widgets\Widget;

class ActivationFunnel extends Widget
{
    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.super-admin.widgets.activation-funnel';

    protected function getViewData(): array
    {
        $totalTenants = Tenant::count();

        $onboardingComplete = Tenant::whereNotNull('onboarding_completed_at')->count();

        $withFirstOrder = Tenant::whereHas('orders')->count();

        $withSecondOrder = Tenant::whereIn('id', function ($query) {
            $query->select('tenant_id')
                ->from('orders')
                ->groupBy('tenant_id')
                ->havingRaw('COUNT(*) >= 2');
        })->count();

        $subscribed = Tenant::whereNotNull('subscribed_at')
            ->where('is_active', true)
            ->count();

        $stages = [
            ['label' => 'Signup', 'count' => $totalTenants],
            ['label' => 'Onboarding', 'count' => $onboardingComplete],
            ['label' => '1st Order', 'count' => $withFirstOrder],
            ['label' => '2nd Order', 'count' => $withSecondOrder],
            ['label' => 'Subscribed', 'count' => $subscribed],
        ];

        // Calculate conversion percentages
        for ($i = 0; $i < count($stages); $i++) {
            $stages[$i]['percentage'] = $totalTenants > 0
                ? round(($stages[$i]['count'] / $totalTenants) * 100, 1)
                : 0;

            $stages[$i]['conversion'] = $i > 0 && $stages[$i - 1]['count'] > 0
                ? round(($stages[$i]['count'] / $stages[$i - 1]['count']) * 100, 1)
                : 100;
        }

        return [
            'stages' => $stages,
        ];
    }
}
