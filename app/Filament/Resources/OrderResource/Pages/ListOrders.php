<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('admin.exports', ['type' => 'orders']))
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
