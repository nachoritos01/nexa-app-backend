<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Concerns\NotifiesNearLimit;
use App\Filament\Resources\LocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    use NotifiesNearLimit;

    protected static string $resource = LocationResource::class;

    protected function afterCreate(): void
    {
        $this->checkNearLimit('branches');
    }
}
