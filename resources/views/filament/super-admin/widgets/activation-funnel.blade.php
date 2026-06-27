<x-filament-widgets::widget>
    <x-filament::section heading="Activation Funnel">
        <div class="space-y-3">
            @foreach ($stages as $i => $stage)
                <div class="flex items-center gap-4">
                    <div class="w-28 text-sm font-medium text-gray-700 dark:text-gray-300 shrink-0">
                        {{ $stage['label'] }}
                    </div>
                    <div class="flex-1">
                        <div class="relative h-8 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                            <div
                                class="absolute inset-y-0 left-0 rounded-lg transition-all duration-500 {{ $i === 0 ? 'bg-primary-500' : ($stage['conversion'] >= 50 ? 'bg-success-500' : 'bg-warning-500') }}"
                                style="width: {{ max($stage['percentage'], 2) }}%"
                            ></div>
                            <div class="absolute inset-0 flex items-center px-3">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $stage['count'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="w-20 text-right shrink-0">
                        @if ($i > 0)
                            <span class="text-xs font-medium {{ $stage['conversion'] >= 50 ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}">
                                {{ $stage['conversion'] }}%
                            </span>
                        @else
                            <span class="text-xs text-gray-400">100%</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
            Percentages show conversion between consecutive stages.
        </p>
    </x-filament::section>
</x-filament-widgets::widget>
