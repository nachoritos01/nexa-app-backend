<x-layouts.saas title="Pricing — SaaS Template">
    <section class="py-20 bg-gradient-to-br from-blue-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900">Simple, transparent pricing</h1>
                <p class="mt-4 text-lg text-gray-600">Choose the plan that fits your business. Change or cancel anytime.</p>
            </div>

            <div class="max-w-5xl mx-auto" x-data="{ yearly: false }">
                {{-- Monthly/Annual toggle --}}
                <div class="flex justify-center mb-12">
                    <div class="bg-gray-100 rounded-full p-1 flex items-center gap-1">
                        <button @click="yearly = false" :class="!yearly ? 'bg-white shadow text-gray-900' : 'text-gray-500'" class="px-4 py-2 rounded-full text-sm font-medium transition">
                            Monthly
                        </button>
                        <button @click="yearly = true" :class="yearly ? 'bg-white shadow text-gray-900' : 'text-gray-500'" class="px-4 py-2 rounded-full text-sm font-medium transition">
                            Annual <span class="text-green-600 font-semibold">-20%</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                @foreach (\App\Enums\PlanType::cases() as $planEnum)
                    @php
                        $planKey = $planEnum->value;
                        $plan = $plans[$planKey];
                        $isGrowth = $planEnum === \App\Enums\PlanType::Growth;
                        $monthlyPrice = $plan['price_monthly'] ?? 0;
                        $yearlyPrice = $plan['price_yearly'] ?? 0;
                        $yearlyMonthly = $yearlyPrice > 0 ? round($yearlyPrice / 12) : 0;
                    @endphp
                    <div class="rounded-2xl border {{ $isGrowth ? 'border-blue-500 ring-2 ring-blue-500' : 'border-gray-200' }} bg-white p-8 shadow-sm relative">
                        @if ($isGrowth)
                            <span class="inline-block rounded-full bg-blue-500 px-4 py-1 text-xs font-semibold text-white mb-3">
                                Popular
                            </span>
                        @endif

                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $plan['label'] ?? ucfirst($planKey) }}</h3>

                        <div class="mb-6">
                            <div x-show="!yearly">
                                <span class="text-4xl font-bold text-gray-900">${{ number_format($monthlyPrice) }}</span>
                                <span class="text-gray-500">/mo</span>
                            </div>
                            <div x-show="yearly" x-cloak>
                                <span class="text-4xl font-bold text-gray-900">${{ number_format($yearlyMonthly) }}</span>
                                <span class="text-gray-500">/mo</span>
                                <p class="text-sm text-green-600 mt-1">${{ number_format($yearlyPrice) }}/yr</p>
                            </div>
                        </div>

                        <ul class="space-y-3 mb-8 text-sm text-gray-600">
                            @foreach($plan['features'] ?? [] as $feature)
                                <li class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <a :href="`{{ route('filament.admin.auth.register') }}?plan={{ $planKey }}&period=${yearly ? 'yearly' : 'monthly'}`"
                           class="block w-full text-center rounded-lg px-4 py-3 text-sm font-semibold transition {{ $isGrowth ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-900 text-white hover:bg-gray-800' }}">
                            Start free trial
                        </a>
                    </div>
                @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Frequently asked questions</h2>

            <div class="space-y-4" x-data="{ open: null }">
                @php
                    $faqs = [
                        ['q' => 'Do I need a credit card to get started?', 'a' => 'No. The 14-day free trial does not require a credit card. Just enter your email and you\'re good to go.'],
                        ['q' => 'Can I change my plan later?', 'a' => 'Yes, you can upgrade or downgrade your plan at any time. The change takes effect immediately and billing is prorated.'],
                        ['q' => 'What happens when my trial ends?', 'a' => 'We\'ll notify you 3 days before. If you don\'t choose a plan, your account is paused but your data remains safe. You can reactivate anytime.'],
                        ['q' => 'Do prices include taxes?', 'a' => 'Prices shown do not include taxes. Applicable taxes are added at checkout and reflected on your invoice.'],
                        ['q' => 'Can I cancel anytime?', 'a' => 'Yes, you can cancel your subscription at any time with no penalty. Your access continues until the end of the paid period.'],
                        ['q' => 'Is my data secure?', 'a' => 'Your data is completely isolated from other businesses. We use encryption in transit and automatic daily backups.'],
                    ];
                @endphp

                @foreach ($faqs as $index => $faq)
                    <div class="border border-gray-200 rounded-xl">
                        <button @click="open = open === {{ $index }} ? null : {{ $index }}" class="w-full flex justify-between items-center p-5 text-left">
                            <span class="font-medium text-gray-900">{{ $faq['q'] }}</span>
                            <svg :class="open === {{ $index }} && 'rotate-180'" class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === {{ $index }}" x-collapse x-cloak class="px-5 pb-5 text-sm text-gray-600">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-16 bg-blue-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Ready to organize your business?</h2>
            <p class="text-lg text-blue-100 mb-8">14 days free. No credit card required.</p>
            <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 text-lg font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg">
                Create free account
            </a>
        </div>
    </section>
</x-layouts.saas>
