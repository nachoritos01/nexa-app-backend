<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $tenantId = currentTenant()?->id;

        if (! $tenantId) {
            return [];
        }

        $ttl = config('saas.cache.dashboard_stats_ttl', 60);

        $stats = Cache::remember("dashboard_stats_{$tenantId}", $ttl, function () use ($tenantId) {
            return [
                'items' => Item::where('tenant_id', $tenantId)->count(),
                'orders' => Order::where('tenant_id', $tenantId)->count(),
                'customers' => Customer::where('tenant_id', $tenantId)->count(),
                'revenue' => (float) Payment::where('tenant_id', $tenantId)->sum('amount'),
            ];
        });

        return [
            Stat::make('Items', $stats['items'])
                ->icon('heroicon-o-cube')
                ->color('primary'),
            Stat::make('Orders', $stats['orders'])
                ->icon('heroicon-o-shopping-bag')
                ->color('info'),
            Stat::make('Customers', $stats['customers'])
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make('Revenue', '$' . number_format($stats['revenue'], 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }
}
