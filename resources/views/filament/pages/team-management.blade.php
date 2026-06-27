@php
    $tenant = currentTenant();
    $limits = $tenant?->planLimits() ?? [];
    $usage = $tenant?->usageCounts() ?? [];
    $maxUsers = $limits['users'] ?? null;
    $currentUsers = $usage['users'] ?? 0;
    $percentage = $maxUsers ? (int) round($currentUsers / $maxUsers * 100) : null;
    $atLimit = $tenant?->isAtLimit('users') ?? false;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Usage Header --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Team Members</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        @if($maxUsers)
                            {{ $currentUsers }} of {{ $maxUsers }} users
                        @else
                            {{ $currentUsers }} users (unlimited)
                        @endif
                    </p>
                </div>
                @if($atLimit)
                    <a href="{{ route('filament.admin.pages.billing') }}"
                       class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 transition">
                        Upgrade Plan
                    </a>
                @endif
            </div>

            @if($percentage !== null)
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all {{ $percentage >= 100 ? 'bg-danger-500' : ($percentage >= 80 ? 'bg-warning-500' : 'bg-primary-500') }}"
                         style="width: {{ min($percentage, 100) }}%"></div>
                </div>
            @endif
        </div>

        {{-- Team Table --}}
        {{ $this->table }}

    </div>
</x-filament-panels::page>
