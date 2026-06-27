<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue - Last 30 days';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return hasModule('payments');
    }

    protected function getData(): array
    {
        $startDate = now()->subDays(29)->startOfDay();

        $revenue = Order::where('created_at', '>=', $startDate)
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->selectRaw("created_at::date as date, SUM(total) as total")
            ->groupByRaw('created_at::date')
            ->pluck('total', 'date');

        $data = collect(range(29, 0))->map(function ($daysAgo) use ($revenue) {
            $date = now()->subDays($daysAgo);

            return [
                'date' => $date->format('m/d'),
                'total' => (int) ($revenue[$date->toDateString()] ?? 0),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'fill' => true,
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
