@php
    $tenant = $this->getTenant();
    $plans = $this->getPlanConfig();
    $currentPlan = $this->getCurrentPlan();
    $usage = $this->getUsageData();
    $labels = $this->getResourceLabels();
    $isSubscribed = $tenant?->isSubscribed() ?? false;
    $isOnTrial = $tenant?->isOnTrial() ?? false;
    $reasonLabels = \App\Enums\CancellationReason::options();
    $referral = $this->getReferralData();
    $cancellation = $this->getCancellationInfo();
    $pluginAddons = $this->getPluginAddons();
    $billingHistory = $this->getBillingHistory();
    $nextBillingDate = $this->getNextBillingDate();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Cancellation Notice --}}
        @if($cancellation)
            <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400 shrink-0" />
                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        @if($cancellation['was_charged'])
                            Your {{ $cancellation['plan'] }} plan will be cancelled on {{ $cancellation['ends_at']->format('F j, Y') }}.
                            No additional charges will be made.
                        @else
                            Your trial period ends on {{ $cancellation['ends_at']->format('F j, Y') }}.
                            No charges were made.
                        @endif
                    </p>
                </div>
            </div>
        @endif

        {{-- Current Plan Card --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $tenant?->planLabel() }} Plan
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-white mt-1">
                        @if($cancellation && $cancellation['was_charged'])
                            Active until {{ $cancellation['ends_at']->format('F j, Y') }}
                        @elseif($cancellation)
                            Trial until {{ $cancellation['ends_at']->format('F j, Y') }}
                        @elseif($isSubscribed)
                            Active subscription
                            @if($nextBillingDate)
                                — next charge on {{ $nextBillingDate }}
                            @endif
                        @elseif($isOnTrial)
                            Trial period — {{ $tenant->trialDaysRemaining() }} days remaining
                            @if($nextBillingDate)
                                — first charge on {{ $nextBillingDate }}
                            @endif
                        @else
                            No active subscription
                        @endif
                    </p>
                </div>
                <div>
                    @if($cancellation && $cancellation['was_charged'])
                        <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900 px-3 py-1 text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            Cancelling soon
                        </span>
                    @elseif($cancellation)
                        <span class="inline-flex items-center rounded-full bg-orange-100 dark:bg-orange-900 px-3 py-1 text-sm font-medium text-orange-800 dark:text-orange-200">
                            Trial cancelled
                        </span>
                    @elseif($isSubscribed)
                        <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-3 py-1 text-sm font-medium text-green-800 dark:text-green-200">
                            Active
                        </span>
                    @elseif($isOnTrial)
                        <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900 px-3 py-1 text-sm font-medium text-blue-800 dark:text-blue-200">
                            Trial
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-sm font-medium text-gray-800 dark:text-gray-200">
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Usage Stats --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Usage</h3>
            <div class="space-y-4">
                @foreach($usage as $resource => $data)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-white">
                                {{ $labels[$resource] ?? $resource }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-white">
                                {{ $data['current'] }} / {{ $data['max'] ?? '∞' }}
                            </span>
                        </div>
                        @if($data['max'] !== null)
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ $data['at_limit'] ? 'bg-red-500' : ($data['near_limit'] ? 'bg-yellow-500' : 'bg-primary-500') }}"
                                     style="width: {{ min($data['percentage'], 100) }}%"></div>
                            </div>
                        @else
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full bg-primary-500" style="width: 0%"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Active Add-ons --}}
        @if (!empty($pluginAddons['addons']))
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Active Add-ons</h3>
                    <a href="{{ route('filament.admin.pages.marketplace') }}"
                       class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
                        Manage plugins
                    </a>
                </div>
                <div class="space-y-3">
                    @foreach ($pluginAddons['addons'] as $addon)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $addon['name'] }}</span>
                                @if ($addon['activated_at'])
                                    <span class="text-xs text-gray-400 ml-2">since {{ $addon['activated_at']->format('M j, Y') }}</span>
                                @endif
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $addon['price'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Total add-ons</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $pluginAddons['total_formatted'] }}</span>
                </div>
            </div>
        @endif

        {{-- Billing History --}}
        @if($billingHistory->isNotEmpty())
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Billing History</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 pr-4 font-medium text-gray-500 dark:text-gray-400">Event</th>
                                <th class="text-left py-2 pr-4 font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="text-right py-2 font-medium text-gray-500 dark:text-gray-400">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($billingHistory as $event)
                                <tr>
                                    <td class="py-3 pr-4">
                                        <div class="flex items-center gap-2">
                                            <x-dynamic-component :component="$event->type->icon()" class="w-5 h-5 shrink-0 text-gray-400 dark:text-gray-500" />
                                            <span class="text-gray-900 dark:text-white">{{ $event->description }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 pr-4 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $event->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="py-3 text-right text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $event->formattedAmount() ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Plan Comparison --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6" x-data="{ yearly: false }">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Available Plans</h3>

            {{-- Monthly/Yearly toggle --}}
            <div class="flex justify-center" style="margin: 30px 0">
                <div class="bg-gray-100 dark:bg-gray-700 rounded-full p-1 flex items-center gap-1">
                    <button @click="yearly = false" :class="!yearly ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'" class="px-4 py-2 rounded-full text-sm font-medium transition">
                        Monthly
                    </button>
                    <button @click="yearly = true" :class="yearly ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'" class="px-4 py-2 rounded-full text-sm font-medium transition">
                        Yearly <span class="text-green-600 dark:text-green-400 font-semibold">-20%</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach(\App\Enums\PlanType::cases() as $planEnum)
                @php $planKey = $planEnum->value; @endphp
                    @php
                        $plan = $plans[$planKey] ?? [];
                        $isCurrent = $currentPlan === $planKey;
                        $monthlyPrice = $plan['price_monthly'] ?? 0;
                        $yearlyPrice = $plan['price_yearly'] ?? 0;
                        $yearlyMonthly = $yearlyPrice > 0 ? round($yearlyPrice / 12) : 0;
                    @endphp
                    <div class="flex flex-col rounded-lg border-2 p-6 {{ $isCurrent ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 dark:border-gray-700' }}">
                        <div class="text-center">
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $plan['label'] ?? ucfirst($planKey) }}
                            </h4>
                            @if($isCurrent)
                                <span class="inline-block mt-1 text-xs font-medium text-primary-600 dark:text-primary-400">
                                    Current plan
                                </span>
                            @endif
                            <div class="mt-3">
                                <div x-show="!yearly">
                                    <span class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($monthlyPrice) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-white">/mo</span>
                                </div>
                                <div x-show="yearly" x-cloak>
                                    <span class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($yearlyMonthly) }}</span>
                                    <span class="text-sm text-gray-500 dark:text-white">/mo</span>
                                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">${{ number_format($yearlyPrice) }}/yr</p>
                                </div>
                            </div>
                        </div>

                        <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-white">
                            @foreach($plan['features'] ?? [] as $feature)
                                <li class="flex items-center gap-2">
                                    <x-heroicon-m-check class="w-4 h-4 text-green-500 shrink-0" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-auto pt-4">
                            @if($isCurrent)
                                <button disabled class="w-full rounded-lg bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-500 dark:text-white cursor-not-allowed">
                                    Current plan
                                </button>
                            @elseif(! $isSubscribed && ! $isOnTrial)
                                <a :href="`{{ route('billing.checkout', ['plan' => $planKey]) }}&period=${yearly ? 'yearly' : 'monthly'}`"
                                   class="block w-full rounded-lg bg-primary-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-primary-700 transition">
                                    Subscribe
                                </a>
                            @elseif($isSubscribed)
                                @php
                                    $isUpgrade = $planEnum->order() > \App\Enums\PlanType::from($currentPlan)->order();
                                @endphp
                                <a :href="`{{ route('billing.checkout', ['plan' => $planKey]) }}&period=${yearly ? 'yearly' : 'monthly'}`"
                                   class="block w-full rounded-lg {{ $isUpgrade ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-600 hover:bg-gray-700' }} px-4 py-2 text-center text-sm font-medium text-white transition"
                                   onclick="return confirm('{{ $isUpgrade ? 'Switch to ' . ($plan['label'] ?? ucfirst($planKey)) . ' plan? Charges will be prorated.' : 'Downgrade to ' . ($plan['label'] ?? ucfirst($planKey)) . ' plan? Limits will be adjusted immediately.' }}')">
                                    {{ $isUpgrade ? 'Upgrade' : 'Switch plan' }}
                                </a>
                            @else
                                <a :href="`{{ route('billing.checkout', ['plan' => $planKey]) }}&period=${yearly ? 'yearly' : 'monthly'}`"
                                   class="block w-full rounded-lg bg-primary-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-primary-700 transition">
                                    Subscribe
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Referral Program --}}
        @if(!empty($referral))
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Referral Program</h3>
                <p class="text-sm text-gray-500 dark:text-white mb-4">
                    Share your link and earn extra trial days. Your referral gets +7 days, you earn +15 days per registration and +15 more when they subscribe.
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">Your referral link</label>
                    <div class="flex gap-2">
                        <input type="text" readonly value="{{ $referral['url'] }}"
                               id="referral-url"
                               class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <button type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('referral-url').value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000)"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">
                            Copy
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Code: {{ $referral['code'] }}</p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-3 text-center">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $referral['total'] }}</span>
                        <p class="text-xs text-gray-500 dark:text-white">Referrals</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-3 text-center">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $referral['converted'] }}</span>
                        <p class="text-xs text-gray-500 dark:text-white">Converted</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-3 text-center">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $referral['bonus_days'] }} / {{ $referral['bonus_max'] }}</span>
                        <p class="text-xs text-gray-500 dark:text-white">Days earned</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Subscription Management (for subscribed users) --}}
        @if($isSubscribed && $tenant?->stripe_id)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Manage Subscription</h3>
                        <p class="text-sm text-gray-500 dark:text-white mt-1">
                            Update your payment method, download invoices, or cancel your subscription.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('billing.portal') }}"
                           class="rounded-lg bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Manage payment
                        </a>
                        <button wire:click="openCancelModal"
                                class="rounded-lg bg-red-50 dark:bg-red-900/20 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                            Cancel subscription
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- Cancel Subscription Modal --}}
    @if($this->showCancelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeCancelModal">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Cancel Subscription</h3>
                <p class="text-sm text-gray-500 dark:text-white mb-4">
                    Before you go, it would help us to know why you are cancelling. Your answer is anonymous.
                </p>

                <form wire:submit="submitCancellation">
                    <div class="space-y-3 mb-4">
                        @foreach($reasonLabels as $value => $label)
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" wire:model="cancelReason" value="{{ $value }}"
                                       class="text-primary-600 focus:ring-primary-500">
                                <span class="text-sm text-gray-700 dark:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    @error('cancelReason')
                        <p class="text-sm text-red-500 mb-3">{{ $message }}</p>
                    @enderror

                    <div class="mb-4">
                        <textarea wire:model="cancelDetails"
                                  rows="3"
                                  placeholder="Tell us more (optional)..."
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="closeCancelModal"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            Go back
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition">
                            Proceed with cancellation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
