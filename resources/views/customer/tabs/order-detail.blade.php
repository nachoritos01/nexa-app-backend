<div>
    {{-- Back link --}}
    <a href="{{ route('customer.orders') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary-600 mb-4 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to orders
    </a>

    <div class="flex items-start justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-900">Order #{{ $order->id }}</h2>
        <div class="flex items-center gap-2">
            @if($order->status !== \App\Enums\OrderStatus::Cancelled)
                <a href="{{ route('customer.order.pdf', $order) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    PDF
                </a>
            @endif
            <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $order->status->badgeClasses() }}">
                {{ $order->status_label }}
            </span>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Timeline</h3>
        @php
            $steps = [
                ['key' => 'pending', 'label' => 'Pending', 'date' => $order->created_at, 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['key' => 'confirmed', 'label' => 'Confirmed', 'date' => $order->confirmed_at, 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['key' => 'in_progress', 'label' => 'In Progress', 'date' => null, 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                ['key' => 'completed', 'label' => 'Completed', 'date' => $order->completed_at, 'icon' => 'M5 13l4 4L19 7'],
            ];
            $statusOrder = ['draft', 'pending', 'confirmed', 'in_progress', 'completed'];
            $currentStatusValue = $order->status->value;
            $currentIndex = array_search($currentStatusValue, $statusOrder);
            if ($order->status === \App\Enums\OrderStatus::Cancelled) $currentIndex = -1;
        @endphp

        <div class="relative">
            @foreach($steps as $i => $step)
                @php
                    $stepIndex = array_search($step['key'], $statusOrder);
                    $isCompleted = $step['date'] !== null || ($stepIndex !== false && $currentIndex !== false && $stepIndex <= $currentIndex);
                    $isCurrent = $step['key'] === $currentStatusValue;
                @endphp
                <div class="flex gap-4 {{ !$loop->last ? 'pb-6' : '' }}">
                    {{-- Line + dot --}}
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0
                            {{ $isCompleted ? 'bg-primary-600 text-white' : ($isCurrent ? 'bg-primary-100 text-primary-600 ring-4 ring-primary-50' : 'bg-gray-100 text-gray-400') }}
                            {{ $isCurrent && !$isCompleted ? 'animate-pulse' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                            </svg>
                        </div>
                        @if(!$loop->last)
                            <div class="w-0.5 flex-1 {{ $isCompleted ? 'bg-primary-600' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                    {{-- Content --}}
                    <div class="pb-1">
                        <p class="text-sm font-medium {{ $isCompleted || $isCurrent ? 'text-gray-900' : 'text-gray-400' }}">{{ $step['label'] }}</p>
                        @if($step['date'])
                            <p class="text-xs text-gray-500">{{ $step['date']->format('Y-m-d H:i') }}</p>
                        @endif
                    </div>
                </div>
            @endforeach

            @if($order->status === \App\Enums\OrderStatus::Cancelled)
                <div class="flex gap-4 pt-2">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-red-100 text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-red-600">Cancelled</p>
                        @if($order->cancelled_at)
                            <p class="text-xs text-gray-500">{{ $order->cancelled_at->format('Y-m-d H:i') }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Items</h3>
        <div class="divide-y divide-gray-100">
            @foreach($order->lines as $line)
                <div class="py-3 flex items-start gap-4 {{ $loop->first ? 'pt-0' : '' }}">
                    <div class="w-14 h-14 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $line->item?->name ?? $line->description ?? 'Item' }}</p>
                        <p class="text-xs text-gray-500">Qty: {{ $line->quantity }}</p>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">${{ number_format($line->subtotal, 2) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Location --}}
    @if($order->location)
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Location</h3>
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $order->location->name }}</p>
                @if($order->location->address)
                    <p class="text-xs text-gray-500">{{ $order->location->address }}, {{ $order->location->city }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Payment summary --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Payments</h3>

        @if($order->payments->isNotEmpty())
            <div class="space-y-2 mb-4">
                @foreach($order->payments as $payment)
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">{{ $payment->received_at?->format('Y-m-d') ?? $payment->created_at->format('Y-m-d') }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $payment->method->badgeClasses() }}">
                                {{ $payment->method->label() }}
                            </span>
                        </div>
                        <span class="font-medium text-gray-900">${{ number_format($payment->amount, 2) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="border-t border-gray-100 pt-3 space-y-1">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Subtotal</span>
                <span class="text-gray-900">${{ number_format(($order->subtotal ?? $order->total), 2) }}</span>
            </div>
            @if($order->tax > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Tax</span>
                    <span class="text-gray-900">${{ number_format($order->tax, 2) }}</span>
                </div>
            @endif
            @if($order->discount_amount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Discount</span>
                    <span class="text-green-600">-${{ number_format($order->discount_amount, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between text-sm font-semibold">
                <span class="text-gray-900">Total</span>
                <span class="text-gray-900">${{ number_format($order->total, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Paid</span>
                <span class="text-green-600">${{ number_format($order->total_paid, 2) }}</span>
            </div>
            @if($order->balance > 0)
                <div class="flex justify-between text-sm font-semibold">
                    <span class="text-amber-600">Outstanding balance</span>
                    <span class="text-amber-600">${{ number_format($order->balance, 2) }}</span>
                </div>
            @endif
        </div>
    </div>

    @if($order->notes)
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Notes</h3>
            <p class="text-sm text-gray-600">{{ $order->notes }}</p>
        </div>
    @endif
</div>
