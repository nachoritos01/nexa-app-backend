<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class MonthlyComparison extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 12;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        if (! hasModule('payments')) {
            return false;
        }

        return auth()->user()?->can('reports.export') ?? false;
    }

    public function getHeading(): ?string
    {
        $current = now()->translatedFormat('F');
        $previous = now()->subMonth()->translatedFormat('F');

        return "Comparison: {$current} vs {$previous}";
    }

    protected function getData(): array
    {
        $currentStart = now()->startOfMonth();
        $currentEnd = now()->endOfMonth();
        $previousStart = now()->subMonth()->startOfMonth();
        $previousEnd = now()->subMonth()->endOfMonth();

        $daysInCurrent = $currentStart->daysInMonth;
        $daysInPrevious = $previousStart->daysInMonth;
        $maxDays = max($daysInCurrent, $daysInPrevious);

        $currentData = Order::query()
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->whereNull('deleted_at')
            ->selectRaw('EXTRACT(DAY FROM created_at)::int as day, COUNT(*) as total')
            ->groupByRaw('EXTRACT(DAY FROM created_at)')
            ->pluck('total', 'day');

        $previousData = Order::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->whereNull('deleted_at')
            ->selectRaw('EXTRACT(DAY FROM created_at)::int as day, COUNT(*) as total')
            ->groupByRaw('EXTRACT(DAY FROM created_at)')
            ->pluck('total', 'day');

        $labels = [];
        $currentValues = [];
        $previousValues = [];

        for ($day = 1; $day <= $maxDays; $day++) {
            $labels[] = (string) $day;
            $currentValues[] = $day <= $daysInCurrent ? ($currentData[$day] ?? 0) : null;
            $previousValues[] = $day <= $daysInPrevious ? ($previousData[$day] ?? 0) : null;
        }

        $currentMonth = now()->translatedFormat('F');
        $previousMonth = now()->subMonth()->translatedFormat('F');

        return [
            'datasets' => [
                [
                    'label' => $currentMonth,
                    'data' => $currentValues,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => $previousMonth,
                    'data' => $previousValues,
                    'backgroundColor' => 'rgba(156, 163, 175, 0.3)',
                    'borderColor' => 'rgb(156, 163, 175)',
                    'borderDash' => [5, 5],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
