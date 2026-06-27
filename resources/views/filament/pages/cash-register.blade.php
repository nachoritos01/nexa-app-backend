<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Date filters --}}
        <x-filament::section>
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex gap-2">
                    <button wire:click="applyPreset('today')"
                        class="px-3 py-1.5 text-sm rounded-lg transition {{ $preset === 'today' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        Today
                    </button>
                    <button wire:click="applyPreset('week')"
                        class="px-3 py-1.5 text-sm rounded-lg transition {{ $preset === 'week' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        This week
                    </button>
                    <button wire:click="applyPreset('month')"
                        class="px-3 py-1.5 text-sm rounded-lg transition {{ $preset === 'month' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        This month
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <input type="date" wire:model.live="dateFrom"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm">
                    <span class="text-gray-500 dark:text-gray-400">to</span>
                    <input type="date" wire:model.live="dateTo"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm">
                </div>
            </div>
        </x-filament::section>

        {{-- Method cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            @foreach ($methods as $method)
                <div class="rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $method['label'] }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">${{ number_format($method['total'], 2) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $method['count'] }} {{ $method['count'] === 1 ? 'payment' : 'payments' }}</p>
                </div>
            @endforeach
        </div>

        {{-- Grand total --}}
        <div class="rounded-xl bg-primary-50 dark:bg-primary-900/20 p-6 border border-primary-200 dark:border-primary-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400">Period Total</p>
                    <p class="text-3xl font-bold text-primary-900 dark:text-primary-100">${{ number_format($grandTotal, 2) }}</p>
                    <p class="text-sm text-primary-600 dark:text-primary-400">{{ $totalCount }} {{ $totalCount === 1 ? 'payment' : 'payments' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">vs previous period</p>
                    <p class="text-lg font-semibold {{ $percentChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $percentChange >= 0 ? '+' : '' }}{{ $percentChange }}%
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">${{ number_format($prevTotal, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Detailed payments table --}}
        <x-filament::section>
            <x-slot name="heading">Payment Details</x-slot>

            @if ($payments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 text-gray-500 dark:text-gray-400 font-medium">Order</th>
                                <th class="text-left py-2 text-gray-500 dark:text-gray-400 font-medium">Customer</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400 font-medium">Amount</th>
                                <th class="text-left py-2 text-gray-500 dark:text-gray-400 font-medium">Method</th>
                                <th class="text-left py-2 text-gray-500 dark:text-gray-400 font-medium">Reference</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400 font-medium">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 text-gray-900 dark:text-gray-100">#{{ $payment->order_id }}</td>
                                    <td class="py-2 text-gray-600 dark:text-gray-400">{{ $payment->order?->customer_name ?? '-' }}</td>
                                    <td class="py-2 text-right text-gray-900 dark:text-gray-100 font-medium">${{ number_format($payment->amount, 2) }}</td>
                                    <td class="py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $payment->method?->badgeClasses() ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ $payment->method?->label() ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-gray-500 dark:text-gray-400 text-xs">{{ $payment->reference ?? '-' }}</td>
                                    <td class="py-2 text-right text-gray-500 dark:text-gray-400 text-xs">{{ $payment->received_at?->format('m/d H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No payments in this period</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
