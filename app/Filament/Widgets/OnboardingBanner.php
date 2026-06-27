<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class OnboardingBanner extends Widget
{
    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.onboarding-banner';

    public static function canView(): bool
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return false;
        }

        return ! $tenant->isOnboardingComplete();
    }

    public function getProgress(): int
    {
        return currentTenant()?->onboardingProgress() ?? 0;
    }

    public function getCompletedSteps(): int
    {
        return count(currentTenant()?->onboarding_steps ?? []);
    }

    public function getTotalSteps(): int
    {
        return count(config('saas.onboarding.steps', []));
    }
}
