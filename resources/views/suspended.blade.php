<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Suspended</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        {{-- Header --}}
        <div class="text-center mb-12">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Your trial period has ended</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Your account has been suspended. Your data is safe — subscribe to a plan to reactivate your account.
            </p>
        </div>

        {{-- Plan cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            @foreach (\App\Enums\PlanType::cases() as $planEnum)
                @php
                    $planKey = $planEnum->value;
                    $plan = config("saas.plans.{$planKey}", []);
                    $isGrowth = $planEnum === \App\Enums\PlanType::Growth;
                @endphp
                <div class="rounded-2xl border {{ $isGrowth ? 'border-blue-500 ring-2 ring-blue-500' : 'border-gray-200' }} bg-white p-8 shadow-sm">
                    @if ($isGrowth)
                        <span class="inline-block rounded-full bg-blue-500 px-4 py-1 text-xs font-semibold text-white mb-3">
                            Popular
                        </span>
                    @endif

                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $plan['label'] ?? ucfirst($planKey) }}</h3>

                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900">${{ number_format($plan['price_monthly'] ?? 0) }}</span>
                        <span class="text-gray-500">/mo</span>
                    </div>

                    <ul class="space-y-3 mb-8 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $plan['max_orders'] ? $plan['max_orders'] . ' orders/month' : 'Unlimited orders' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $plan['max_users'] ? $plan['max_users'] . ' users' : 'Unlimited users' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $plan['max_locations'] ? $plan['max_locations'] . ($plan['max_locations'] === 1 ? ' location' : ' locations') : 'Unlimited locations' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $plan['max_items'] ? $plan['max_items'] . ' items' : 'Unlimited items' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ $plan['max_customers'] ? number_format($plan['max_customers']) . ' customers' : 'Unlimited customers' }}
                        </li>
                    </ul>

                    <a href="{{ route('billing.checkout', ['plan' => $planKey, 'period' => 'monthly']) }}"
                       class="block w-full text-center rounded-lg px-4 py-3 text-sm font-semibold transition {{ $isGrowth ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-900 text-white hover:bg-gray-800' }}">
                        Subscribe to {{ $plan['label'] ?? ucfirst($planKey) }}
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Footer links --}}
        <div class="text-center space-y-4">
            <p class="text-gray-500 text-sm">
                Need help?
                <a href="mailto:{{ config('business.contact.email') }}" class="text-blue-600 hover:underline">Contact support</a>
            </p>
            <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-gray-400 hover:text-gray-600 underline">
                    Sign out
                </button>
            </form>
        </div>
    </div>
</body>
</html>
