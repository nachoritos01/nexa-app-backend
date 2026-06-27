<div>
    <h2 class="text-xl font-bold text-gray-900 mb-6">My Orders</h2>

    {{-- Filters --}}
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        @foreach(['todos' => 'All', 'activos' => 'Active', 'entregados' => 'Delivered', 'cancelados' => 'Cancelled'] as $key => $label)
            <a href="{{ route('customer.orders', ['estado' => $key]) }}"
               class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors {{ $currentFilter === $key ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Orders list --}}
    @if($orders->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500 mb-4">You have no orders {{ $currentFilter !== 'todos' ? 'matching this filter' : 'yet' }}.</p>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-medium px-6 py-3 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Contact us to place an order
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($orders as $order)
                <a href="{{ route('customer.order.detail', $order) }}" class="block bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="text-sm font-semibold text-gray-900">Order #{{ $order->id }}</span>
                            <span class="text-sm text-gray-400 ml-2">{{ $order->created_at->format('d/m/Y') }}</span>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $order->status->badgeClasses() }}">
                            {{ $order->status_label }}
                        </span>
                    </div>

                    {{-- Items preview (max 3) --}}
                    <div class="text-sm text-gray-600 mb-3">
                        @foreach($order->lines->take(3) as $item)
                            <span>{{ $item->quantity }}x {{ $item->item?->name ?? $item->description ?? 'Item' }}</span>
                            @if(!$loop->last), @endif
                        @endforeach
                        @if($order->lines->count() > 3)
                            <span class="text-gray-400">+{{ $order->lines->count() - 3 }} more</span>
                        @endif
                    </div>

                    {{-- Totals --}}
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-gray-900">${{ number_format($order->total, 2) }}</span>
                        @if($order->balance > 0)
                            <span class="text-amber-600 font-medium">Balance: ${{ number_format($order->balance, 2) }}</span>
                        @else
                            <span class="text-green-600 font-medium">Paid</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $orders->withQueryString()->links() }}
        </div>
    @endif
</div>
