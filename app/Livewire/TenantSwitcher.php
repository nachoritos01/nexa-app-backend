<?php

namespace App\Livewire;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Spatie\Permission\PermissionRegistrar;

class TenantSwitcher extends Component
{
    public int $currentTenantId = 0;

    public function mount(): void
    {
        $this->currentTenantId = (int) session('tenant_id', 0);
    }

    public function switchTenant(int $tenantId): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $tenant = $user->tenants()->where('tenant_id', $tenantId)->where('is_active', true)->first();
        if (! $tenant) {
            return;
        }

        session(['tenant_id' => $tenant->id]);
        app()->instance('currentTenant', $tenant);

        // Sync Spatie role for new tenant
        $pivotRole = $tenant->pivot?->role;
        if ($pivotRole) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            $user->syncRoles([$pivotRole]);
        }

        $this->currentTenantId = $tenant->id;

        $this->redirect(route('filament.admin.pages.dashboard'));
    }

    /** @return Collection<int, Tenant> */
    public function getUserTenantsProperty(): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        return $user->tenants()->where('is_active', true)->get();
    }

    public function render(): View
    {
        return view('livewire.tenant-switcher');
    }
}
