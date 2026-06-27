<div>
    @if($this->userTenants->count() > 1)
        <x-filament::dropdown placement="bottom-end">
            <x-slot name="trigger">
                <button type="button" class="flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5">
                    <x-heroicon-m-building-office class="h-5 w-5" />
                    <span>{{ $this->userTenants->firstWhere('id', $currentTenantId)?->name ?? 'Select' }}</span>
                    <x-heroicon-m-chevron-down class="h-4 w-4" />
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                @foreach($this->userTenants as $tenant)
                    <x-filament::dropdown.list.item
                        wire:click="switchTenant({{ $tenant->id }})"
                        :icon="$tenant->id === $currentTenantId ? 'heroicon-m-check' : 'heroicon-m-building-office'"
                        :color="$tenant->id === $currentTenantId ? 'primary' : 'gray'"
                    >
                        {{ $tenant->name }}
                    </x-filament::dropdown.list.item>
                @endforeach
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    @endif
</div>
