<?php

namespace App\Filament\SuperAdmin\Resources\TenantResource\Pages;

use App\Filament\SuperAdmin\Resources\TenantResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ?->withCount(['plugins as active_plugins_count' => fn (Builder $q) => $q->where('tenant_plugins.is_active', true)])
            ->with(['plugins' => fn ($q) => $q->wherePivot('is_active', true)]);
    }
}
