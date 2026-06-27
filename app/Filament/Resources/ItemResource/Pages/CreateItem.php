<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Concerns\NotifiesNearLimit;
use App\Filament\Resources\ItemResource;
use App\Models\FeatureUsage;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    use NotifiesNearLimit;

    protected static string $resource = ItemResource::class;

    protected function afterCreate(): void
    {
        FeatureUsage::track('product_created');
        $this->checkNearLimit('products');
    }
}
