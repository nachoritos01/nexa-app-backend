<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('admin.exports', ['type' => 'customers']))
                ->visible(function (): bool {
                    $tenant = currentTenant();

                    return $tenant
                        && $tenant->plan !== 'starter'
                        && auth()->user()?->can('reports.export');
                }),
            Actions\CreateAction::make(),
        ];
    }
}
