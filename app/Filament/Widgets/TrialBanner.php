<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TrialBanner extends Widget
{
    protected static ?int $sort = -2;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.trial-banner';

    public static function canView(): bool
    {
        if (! hasModule('payments')) {
            return false;
        }

        $tenant = currentTenant();

        if (! $tenant) {
            return false;
        }

        return $tenant->isOnTrial() || $tenant->isTrialExpired();
    }

    public function getTenant(): ?\App\Models\Tenant
    {
        return currentTenant();
    }
}
