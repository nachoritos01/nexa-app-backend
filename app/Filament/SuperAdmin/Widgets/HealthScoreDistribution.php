<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Tenant;
use Filament\Widgets\ChartWidget;

class HealthScoreDistribution extends ChartWidget
{
    protected static ?string $heading = 'Health Score Distribution';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $healthy = Tenant::where('is_active', true)->where('health_score', '>=', 70)->count();
        $atRisk = Tenant::where('is_active', true)->where('health_score', '>=', 50)->where('health_score', '<', 70)->count();
        $critical = Tenant::where('is_active', true)->whereNotNull('health_score')->where('health_score', '<', 50)->count();
        $noData = Tenant::where('is_active', true)->whereNull('health_score')->count();

        return [
            'datasets' => [
                [
                    'data' => [$healthy, $atRisk, $critical, $noData],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(234, 179, 8)',
                        'rgb(239, 68, 68)',
                        'rgb(156, 163, 175)',
                    ],
                ],
            ],
            'labels' => ['Healthy', 'At Risk', 'Critical', 'No Data'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
