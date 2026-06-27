<?php

namespace App\Filament\SuperAdmin\Resources\PluginResource\Pages;

use App\Filament\SuperAdmin\Resources\PluginResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlugins extends ListRecords
{
    protected static string $resource = PluginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
