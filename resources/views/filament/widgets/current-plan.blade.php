@php
    $data = $this->getPlanData();
    $plan = $data['plan'] ?? 'Starter';
    $planKey = $data['plan_key'] ?? 'starter';
    $status = $data['status'] ?? 'trial';
    $daysRemaining = $data['days_remaining'] ?? 0;
    $showUpgrade = $data['show_upgrade'] ?? false;

    $statusLabel = match($status) {
        'active' => 'Active subscription',
        'expired' => 'Trial expired',
        default => "Trial: {$daysRemaining} " . ($daysRemaining === 1 ? 'day' : 'days') . " remaining",
    };

    $statusColor = match($status) {
        'active' => 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-950',
        'expired' => 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-950',
        default => 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-950',
    };
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Your Plan</h3>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $plan }}</p>
                @if ($status === 'trial')
                    <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        @php
                            $trialDays = config('saas.trial.days', 14);
                            $progress = max(0, min(100, (($trialDays - $daysRemaining) / $trialDays) * 100));
                        @endphp
                        <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                    </div>
                @endif
            </div>

            @if ($showUpgrade)
                <a href="{{ route('filament.admin.pages.billing') }}"
                   class="block w-full text-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition">
                    Upgrade
                </a>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Top plan active</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
