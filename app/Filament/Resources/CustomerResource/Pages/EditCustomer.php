<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['portal_enabled'])) {
            $data['password'] = null;
        } elseif (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }

        unset($data['portal_enabled']);

        return $data;
    }
}
