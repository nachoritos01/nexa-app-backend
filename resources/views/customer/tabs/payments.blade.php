<div>
    <h2 class="text-xl font-bold text-gray-900 mb-6">Payments</h2>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-sm text-gray-500 mb-1">Total Paid</p>
            <p class="text-2xl font-bold text-green-600">${{ number_format($totalPaid, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-sm text-gray-500 mb-1">Pending Balance</p>
            <p class="text-2xl font-bold {{ $pendingBalance > 0 ? 'text-amber-600' : 'text-gray-400' }}">${{ number_format($pendingBalance, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-sm text-gray-500 mb-1">Active Orders</p>
            <p class="text-2xl font-bold text-primary-600">{{ $activeOrders }}</p>
        </div>
    </div>

    {{-- Orders with balance --}}
    @if($ordersWithBalance->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Orders with Pending Balance</h3>
            <div class="space-y-3">
                @foreach($ordersWithBalance as $order)
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <a href="{{ route('customer.order.detail', $order) }}" class="text-sm font-medium text-primary-600 hover:underline">
                                Order #{{ $order->id }}
                            </a>
                            <p class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-amber-600">${{ number_format($order->balance, 2) }}</span>
                            @if($order->payment_link_url)
                                <a href="{{ $order->payment_link_url }}" target="_blank"
                                   class="bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium py-1.5 px-4 rounded-lg transition-colors">
                                    Pay
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Payment history --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Payment History</h3>

        @if($allPayments->isEmpty())
            <p class="text-sm text-gray-500 text-center py-8">No payments recorded.</p>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-6 py-2 text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="text-left px-6 py-2 text-xs font-semibold text-gray-500 uppercase">Order</th>
                            <th class="text-left px-6 py-2 text-xs font-semibold text-gray-500 uppercase">Method</th>
                            <th class="text-right px-6 py-2 text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($allPayments as $payment)
                            <tr>
                                <td class="px-6 py-3 text-gray-600">{{ ($payment->received_at ?? $payment->created_at)->format('d/m/Y') }}</td>
                                <td class="px-6 py-3">
                                    <a href="{{ route('customer.order.detail', $payment->order_id) }}" class="text-primary-600 hover:underline">#{{ $payment->order_id }}</a>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $payment->method->badgeClasses() }}">
                                        {{ $payment->method->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right font-semibold text-gray-900">${{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Payment methods info --}}
    <div class="mt-6 bg-blue-50 border border-blue-100 rounded-2xl p-5">
        <h4 class="text-sm font-semibold text-blue-900 mb-2">Accepted payment methods</h4>
        <div class="flex flex-wrap gap-3 text-sm text-blue-700">
            <span class="flex items-center gap-1">Cash</span>
            <span class="text-blue-300">|</span>
            <span class="flex items-center gap-1">Bank transfer</span>
            <span class="text-blue-300">|</span>
            <span class="flex items-center gap-1">Credit/debit card</span>
        </div>
    </div>
</div>
