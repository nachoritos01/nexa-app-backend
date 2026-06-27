<x-layouts.app title="Payment Status">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            @if ($status === 'success')
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful</h1>
            @else
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Error</h1>
            @endif

            <p class="text-lg text-gray-600 mb-8">{{ $message }}</p>

            @if ($order)
                <div class="card text-left">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Details</h2>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Order</dt>
                            <dd class="font-medium text-gray-900">#{{ $order->id }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Customer</dt>
                            <dd class="font-medium text-gray-900">{{ $order->customer_name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Total</dt>
                            <dd class="font-medium text-gray-900">${{ number_format($order->total, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Paid</dt>
                            <dd class="font-medium text-green-600">${{ number_format($order->total_paid, 2) }}</dd>
                        </div>
                        <div class="flex justify-between border-t pt-2">
                            <dt class="text-gray-500 font-semibold">Balance</dt>
                            <dd class="font-bold {{ $order->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                ${{ number_format($order->balance, 2) }}
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif

            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ config('business.contact.url', 'mailto:' . config('business.contact.email')) }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Contact us
                </a>
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
