<x-filament-panels::page>
    @php
        $categories = $this->getPluginsByCategory();
        $hasSubscription = $this->tenantHasSubscription();
        $categoryLabels = [
            'billing' => 'Billing',
            'engagement' => 'Engagement',
            'operations' => 'Operations',
            'developer' => 'Developer',
            'reporting' => 'Reporting',
            'general' => 'General',
        ];
    @endphp

    @forelse ($categories as $category => $plugins)
        <x-filament::section>
            <x-slot name="heading">{{ $categoryLabels[$category] ?? ucfirst($category) }}</x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($plugins as $plugin)
                    @php
                        $isPaid = ! $plugin->is_free && ! $plugin->is_included;
                        $needsSubscription = $isPaid && ! $hasSubscription && ! $plugin->is_enabled_for_tenant;
                    @endphp
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex flex-col gap-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center">
                                    <x-dynamic-component :component="$plugin->icon" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $plugin->name }}</h3>
                                    @if ($plugin->is_included)
                                        <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/20 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                                            Included
                                        </span>
                                    @elseif ($plugin->is_free)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-400">
                                            Free
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400">
                                            {{ $plugin->formattedPrice() }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400 flex-1">
                            {{ $plugin->description }}
                        </p>

                        <div class="flex items-center justify-end gap-2">
                            @if ($needsSubscription)
                                <a href="{{ route('filament.admin.pages.billing') }}"
                                   class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    <x-heroicon-m-lock-closed class="w-3 h-3" />
                                    Subscribe first
                                </a>
                            @else
                                <span
                                    wire:loading
                                    wire:target="togglePlugin({{ $plugin->id }})"
                                    class="inline-block"
                                >
                                    <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                                </span>
                                <button
                                    type="button"
                                    wire:click="togglePlugin({{ $plugin->id }})"
                                    wire:loading.attr="disabled"
                                    aria-label="Toggle {{ $plugin->name }}"
                                    @class([
                                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                                        'bg-primary-600' => $plugin->is_enabled_for_tenant,
                                        'bg-gray-200 dark:bg-gray-700' => ! $plugin->is_enabled_for_tenant,
                                    ])
                                    role="switch"
                                    aria-checked="{{ $plugin->is_enabled_for_tenant ? 'true' : 'false' }}"
                                >
                                    <span
                                        @class([
                                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            'translate-x-5' => $plugin->is_enabled_for_tenant,
                                            'translate-x-0' => ! $plugin->is_enabled_for_tenant,
                                        ])
                                    ></span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @empty
        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
            No plugins available.
        </div>
    @endforelse

    {{-- Confirmation Modal for Paid Plugins --}}
    @if ($this->showConfirmModal)
        @php $pendingPlugin = $this->getPendingPlugin(); @endphp
        @if ($pendingPlugin)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeConfirmModal">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ $this->pendingAction === 'activate' ? 'Activate' : 'Deactivate' }} {{ $pendingPlugin->name }}?
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        @if ($this->pendingAction === 'activate')
                            This will add <strong>{{ $pendingPlugin->formattedPrice() }}</strong> to your subscription. You will be charged immediately (prorated).
                        @else
                            This will remove <strong>{{ $pendingPlugin->formattedPrice() }}</strong> from your subscription. The change takes effect immediately.
                        @endif
                    </p>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="closeConfirmModal"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            Cancel
                        </button>
                        <button type="button" wire:click="confirmToggle"
                                wire:loading.attr="disabled"
                                @class([
                                    'rounded-lg px-4 py-2 text-sm font-medium text-white transition',
                                    'bg-primary-600 hover:bg-primary-700' => $this->pendingAction === 'activate',
                                    'bg-red-600 hover:bg-red-700' => $this->pendingAction === 'deactivate',
                                ])>
                            <span wire:loading wire:target="confirmToggle">
                                <x-filament::loading-indicator class="h-4 w-4 inline" />
                            </span>
                            {{ $this->pendingAction === 'activate' ? 'Activate & Pay' : 'Deactivate' }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
