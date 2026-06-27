<x-filament-widgets::widget>
    <div class="rounded-xl border border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-950 p-4">
        <div class="flex items-center gap-3">
            <x-heroicon-o-rocket-launch class="w-6 h-6 text-primary-500" />
            <div class="flex-1">
                <p class="font-semibold text-primary-800 dark:text-primary-200">
                    Complete your account setup
                </p>
                <p class="text-sm text-primary-700 dark:text-primary-300">
                    {{ $this->getCompletedSteps() }}/{{ $this->getTotalSteps() }} steps completed
                </p>
            </div>
            <a
                href="{{ url('/admin/onboarding') }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 transition"
            >
                Continue
                <x-heroicon-m-arrow-right class="w-4 h-4" />
            </a>
        </div>
        {{-- Progress bar --}}
        <div class="mt-3 h-2 w-full rounded-full bg-primary-200 dark:bg-primary-800">
            <div
                class="h-2 rounded-full bg-primary-600 transition-all duration-300"
                style="width: {{ $this->getProgress() }}%"
            ></div>
        </div>
    </div>
</x-filament-widgets::widget>
