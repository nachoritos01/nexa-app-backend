<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaasMetrics extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeTenants = Tenant::where('is_active', true)->count();

        $activeTrials = Tenant::where('is_active', true)
            ->whereNull('subscribed_at')
            ->where('trial_ends_at', '>', now())
            ->count();

        $churnedThisMonth = Tenant::where('is_active', false)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // MRR: sum of plan prices for subscribed tenants
        $subscribedTenants = Tenant::whereNotNull('subscribed_at')
            ->where('is_active', true)
            ->get();

        $mrr = $subscribedTenants->sum(function (Tenant $tenant): int {
            return config("saas.plans.{$tenant->plan}.price_monthly", 0);
        });

        return [
            Stat::make('MRR', '$' . number_format($mrr))
                ->description('Monthly Recurring Revenue')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Active Tenants', $activeTenants)
                ->description('Total active')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('Active Trials', $activeTrials)
                ->description('On trial period')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Churned This Month', $churnedThisMonth)
                ->description('Suspended this month')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($churnedThisMonth > 0 ? 'danger' : 'success'),
        ];
    }
}
