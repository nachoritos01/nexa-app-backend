<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Concerns\NotifiesNearLimit;
use App\Filament\Resources\CustomerResource;
use App\Models\FeatureUsage;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    use NotifiesNearLimit;

    protected static string $resource = CustomerResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['portal_enabled'])) {
            unset($data['password']);
        }

        unset($data['portal_enabled']);

        return $data;
    }

    protected function afterCreate(): void
    {
        FeatureUsage::track('customer_created');
        $this->checkNearLimit('customers');
    }
}
