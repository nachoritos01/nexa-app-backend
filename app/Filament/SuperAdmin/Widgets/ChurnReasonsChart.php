<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\CancellationSurvey;
use Filament\Widgets\ChartWidget;

class ChurnReasonsChart extends ChartWidget
{
    protected static ?string $heading = 'Cancellation Reasons';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $reasons = CancellationSurvey::query()
            ->selectRaw('reason, count(*) as total')
            ->groupBy('reason')
            ->pluck('total', 'reason');

        if ($reasons->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['rgb(156, 163, 175)'],
                    ],
                ],
                'labels' => ['No data'],
            ];
        }

        $labels = CancellationSurvey::reasonLabels();
        $colors = [
            'price' => 'rgb(239, 68, 68)',
            'missing_features' => 'rgb(234, 179, 8)',
            'closed_business' => 'rgb(156, 163, 175)',
            'competitor' => 'rgb(249, 115, 22)',
            'other' => 'rgb(107, 114, 128)',
        ];

        return [
            'datasets' => [
                [
                    'data' => $reasons->values()->toArray(),
                    'backgroundColor' => $reasons->keys()->map(fn ($key) => $colors[$key] ?? 'rgb(156, 163, 175)')->toArray(),
                ],
            ],
            'labels' => $reasons->keys()->map(fn ($key) => $labels[$key] ?? $key)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
