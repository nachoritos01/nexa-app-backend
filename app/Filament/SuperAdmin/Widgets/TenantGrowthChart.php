<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Tenant;
use Filament\Widgets\ChartWidget;

class TenantGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'New Tenants (Last 8 Weeks)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $weeks = collect();
        $labels = collect();

        $startDate = now()->subWeeks(7)->startOfWeek();

        $counts = Tenant::where('created_at', '>=', $startDate)
            ->selectRaw("DATE_TRUNC('week', created_at)::date as week, COUNT(*) as count")
            ->groupByRaw("DATE_TRUNC('week', created_at)::date")
            ->pluck('count', 'week');

        for ($i = 7; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weeks->push((int) ($counts[$weekStart->toDateString()] ?? 0));
            $labels->push($weekStart->format('d M'));
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Tenants',
                    'data' => $weeks->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
