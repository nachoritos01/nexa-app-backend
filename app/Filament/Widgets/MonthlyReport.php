<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderLine;
use Filament\Widgets\Widget;

class MonthlyReport extends Widget
{
    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.monthly-report';

    public static function canView(): bool
    {
        return auth()->user()?->can('reports.export') ?? false;
    }

    protected function getViewData(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $totalOrders = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        $totalRevenue = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total');

        $topItems = OrderLine::query()
            ->join('orders', 'order_lines.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', currentTenant()?->id)
            ->whereBetween('orders.created_at', [$startOfMonth, $endOfMonth])
            ->leftJoin('items', 'order_lines.item_id', '=', 'items.id')
            ->selectRaw('COALESCE(items.name, order_lines.description) as title, SUM(order_lines.quantity) as total_qty, SUM(order_lines.subtotal) as total_revenue')
            ->groupByRaw('COALESCE(items.name, order_lines.description)')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $topCustomers = Order::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('customer_name, COUNT(*) as order_count, SUM(total) as total_spent, MAX(created_at) as last_order_at')
            ->groupBy('customer_name')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // Add historical totals in a single query instead of N+1
        if ($topCustomers->isNotEmpty()) {
            $customerNames = $topCustomers->pluck('customer_name')->toArray();
            $historicalTotals = Order::query()
                ->whereIn('customer_name', $customerNames)
                ->selectRaw('customer_name, SUM(total) as historical_total')
                ->groupBy('customer_name')
                ->pluck('historical_total', 'customer_name');

            foreach ($topCustomers as $customer) {
                $customer->historical_total = $historicalTotals[$customer->customer_name] ?? 0;
            }
        }

        return [
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'topProducts' => $topItems,
            'topCustomers' => $topCustomers,
            'monthName' => now()->translatedFormat('F Y'),
        ];
    }
}
