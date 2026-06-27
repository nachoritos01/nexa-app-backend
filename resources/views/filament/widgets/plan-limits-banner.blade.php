@php
    $warnings = $this->getWarnings();
@endphp

<x-filament-widgets::widget>
    <div class="space-y-2">
        @foreach($warnings as $warning)
            @php
                $bgClasses = match($warning['type']) {
                    'danger' => 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800',
                    'warning' => 'bg-yellow-50 dark:bg-yellow-950 border-yellow-200 dark:border-yellow-800',
                    default => 'bg-gray-50 dark:bg-gray-950 border-gray-200 dark:border-gray-800',
                };
                $textClasses = match($warning['type']) {
                    'danger' => 'text-red-800 dark:text-red-200',
                    'warning' => 'text-yellow-800 dark:text-yellow-200',
                    default => 'text-gray-800 dark:text-gray-200',
                };
                $iconClasses = match($warning['type']) {
                    'danger' => 'text-red-500',
                    'warning' => 'text-yellow-500',
                    default => 'text-gray-500',
                };
            @endphp
            <div class="rounded-xl border p-4 {{ $bgClasses }}">
                <div class="flex items-center gap-3">
                    @if($warning['type'] === 'danger')
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 {{ $iconClasses }} shrink-0" />
                    @else
                        <x-heroicon-o-exclamation-circle class="w-5 h-5 {{ $iconClasses }} shrink-0" />
                    @endif
                    <div class="flex-1">
                        <p class="text-sm font-medium {{ $textClasses }}">
                            {{ $warning['message'] }}
                        </p>
                    </div>
                    <a href="{{ route('filament.admin.pages.billing') }}"
                       class="text-sm font-medium {{ $textClasses }} underline hover:no-underline shrink-0">
                        View plans
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
