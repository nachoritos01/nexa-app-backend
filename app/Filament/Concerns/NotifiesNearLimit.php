<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

trait NotifiesNearLimit
{
    protected function checkNearLimit(string $resource): void
    {
        $tenant = currentTenant();

        if (! $tenant || ! $tenant->isNearLimit($resource)) {
            return;
        }

        $percentage = $tenant->usagePercentage($resource);

        Notification::make()
            ->title('Cerca del limite de tu plan')
            ->body("Estas usando el {$percentage}% de tu capacidad de {$resource}.")
            ->warning()
            ->actions([
                Action::make('upgrade')
                    ->label('Ver planes')
                    ->url(route('filament.admin.pages.billing')),
            ])
            ->persistent()
            ->send();
    }
}
