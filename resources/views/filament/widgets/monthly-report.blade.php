<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Monthly Report — {{ $monthName }}
        </x-slot>

        {{-- Summary stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">Orders this month</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $totalOrders }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                <p class="text-sm text-green-600 dark:text-green-400 font-medium">Revenue this month</p>
                <p class="text-2xl font-bold text-green-900 dark:text-green-100">${{ number_format($totalRevenue, 2) }}</p>
            </div>
        </div>

        {{-- Top tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Items --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Top 5 Items</h3>
                @if ($topProducts->isNotEmpty())
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 text-gray-500 dark:text-gray-400 font-medium">Item</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400 font-medium">Qty</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400 font-medium">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProducts as $product)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 text-gray-900 dark:text-gray-100">{{ $product->title }}</td>
                                    <td class="py-2 text-right text-gray-600 dark:text-gray-400">{{ $product->total_qty }}</td>
                                    <td class="py-2 text-right text-gray-900 dark:text-gray-100 font-medium">${{ number_format($product->total_revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No data this month</p>
                @endif
            </div>

            {{-- Top Customers --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Top 5 Customers</h3>
                @if ($topCustomers->isNotEmpty())
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 text-gray-500 dark:text-gray-400 font-medium">Customer</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400 font-medium">Orders</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400 font-medium">Month Total</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400 font-medium">All Time</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400 font-medium">Last</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topCustomers as $customer)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 text-gray-900 dark:text-gray-100">{{ $customer->customer_name }}</td>
                                    <td class="py-2 text-right text-gray-600 dark:text-gray-400">{{ $customer->order_count }}</td>
                                    <td class="py-2 text-right text-gray-900 dark:text-gray-100 font-medium">${{ number_format($customer->total_spent, 2) }}</td>
                                    <td class="py-2 text-right text-gray-500 dark:text-gray-400">${{ number_format($customer->historical_total, 2) }}</td>
                                    <td class="py-2 text-right text-gray-500 dark:text-gray-400 text-xs">{{ \Carbon\Carbon::parse($customer->last_order_at)->format('m/d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No data this month</p>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
