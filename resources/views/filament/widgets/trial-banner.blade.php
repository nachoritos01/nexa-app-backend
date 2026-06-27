@php
    $tenant = $this->getTenant();
    $color = $tenant?->trialColorStatus() ?? 'gray';
    $days = $tenant?->trialDaysRemaining() ?? 0;
    $expired = $tenant?->isTrialExpired() ?? false;
    $grace = $tenant?->isInGracePeriod() ?? false;

    $bgClasses = match($color) {
        'success' => 'bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800',
        'warning' => 'bg-yellow-50 dark:bg-yellow-950 border-yellow-200 dark:border-yellow-800',
        'danger' => 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800',
        default => 'bg-gray-50 dark:bg-gray-950 border-gray-200 dark:border-gray-800',
    };

    $textClasses = match($color) {
        'success' => 'text-green-800 dark:text-green-200',
        'warning' => 'text-yellow-800 dark:text-yellow-200',
        'danger' => 'text-red-800 dark:text-red-200',
        default => 'text-gray-800 dark:text-gray-200',
    };

    $iconClasses = match($color) {
        'success' => 'text-green-500',
        'warning' => 'text-yellow-500',
        'danger' => 'text-red-500',
        default => 'text-gray-500',
    };
@endphp

<x-filament-widgets::widget>
    <div class="rounded-xl border p-4 {{ $bgClasses }}">
        <div class="flex items-center gap-3">
            <x-heroicon-o-clock class="w-6 h-6 {{ $iconClasses }}" />
            <div class="flex-1">
                @if($expired && $grace)
                    <p class="font-semibold {{ $textClasses }}">
                        Your trial period has ended
                    </p>
                    <p class="text-sm {{ $textClasses }} opacity-75">
                        You have a grace period to subscribe and keep your data.
                    </p>
                @elseif($expired)
                    <p class="font-semibold {{ $textClasses }}">
                        Your trial period has expired
                    </p>
                    <p class="text-sm {{ $textClasses }} opacity-75">
                        Subscribe to continue using the platform.
                    </p>
                @else
                    <p class="font-semibold {{ $textClasses }}">
                        Trial period: {{ $days }} {{ $days === 1 ? 'day' : 'days' }} remaining
                    </p>
                    <p class="text-sm {{ $textClasses }} opacity-75">
                        Explore all features without limits during your free trial.
                    </p>
                @endif
            </div>
            <a href="{{ route('filament.admin.pages.billing') }}"
               class="shrink-0 rounded-lg bg-white/80 dark:bg-gray-800/80 px-4 py-2 text-sm font-medium {{ $textClasses }} hover:bg-white dark:hover:bg-gray-800 transition border {{ match($color) { 'success' => 'border-green-300 dark:border-green-700', 'warning' => 'border-yellow-300 dark:border-yellow-700', 'danger' => 'border-red-300 dark:border-red-700', default => 'border-gray-300 dark:border-gray-700' } }}">
                Subscribe
            </a>
        </div>
    </div>
</x-filament-widgets::widget>
