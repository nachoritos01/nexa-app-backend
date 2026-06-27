<div>
    <h2 class="text-xl font-bold text-gray-900 mb-6">Loyalty Program</h2>

    {{-- Section 1: Level Card --}}
    @php
        $tier = $customer->loyalty_tier;
        $nextTier = $tier->nextTier();
        $progress = 0;
        $pointsToNext = 0;
        if ($nextTier) {
            $range = $nextTier->minPoints() - $tier->minPoints();
            $earned = $customer->loyalty_lifetime_points - $tier->minPoints();
            $progress = $range > 0 ? min(100, round(($earned / $range) * 100)) : 0;
            $pointsToNext = max(0, $nextTier->minPoints() - $customer->loyalty_lifetime_points);
        } else {
            $progress = 100;
        }
        $tierStyles = [
            0 => ['gradient' => 'from-amber-600 to-amber-800', 'shadow' => 'shadow-amber-200'],
            1 => ['gradient' => 'from-slate-300 to-slate-500', 'shadow' => 'shadow-slate-200'],
            2 => ['gradient' => 'from-yellow-400 via-amber-300 to-yellow-500', 'shadow' => 'shadow-yellow-200'],
            3 => ['gradient' => 'from-violet-500 via-purple-600 to-indigo-700', 'shadow' => 'shadow-purple-300'],
        ];
        $style = $tierStyles[$tier->value] ?? ['gradient' => 'from-gray-400 to-gray-600', 'shadow' => 'shadow-gray-200'];
        $gradient = $style['gradient'];
        $shadow = $style['shadow'];
    @endphp

    <div class="bg-gradient-to-r {{ $gradient }} rounded-2xl shadow-lg {{ $shadow }} p-6 text-white mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm font-medium text-white/80">Available Points</p>
                <p class="text-4xl font-bold">{{ number_format($customer->loyalty_points) }}</p>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold">{{ $tier->label() }}</p>
                <p class="text-sm text-white/80">x{{ $tier->multiplier() }} multiplier</p>
            </div>
        </div>

        @if($nextTier)
            <div>
                <div class="flex justify-between text-sm text-white/80 mb-1">
                    <span>Progress to {{ $nextTier->label() }}</span>
                    <span>{{ number_format($customer->loyalty_lifetime_points) }}/{{ number_format($nextTier->minPoints()) }} pts</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-2.5">
                    <div class="bg-white rounded-full h-2.5 transition-all" style="width: {{ $progress }}%"></div>
                </div>
                <p class="text-xs text-white/70 mt-1">{{ number_format($pointsToNext) }} more points to {{ $nextTier->label() }}</p>
            </div>
        @else
            <p class="text-sm text-white/80">You've reached the highest tier!</p>
        @endif
    </div>

    {{-- Section 2: Current Tier Benefits --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Your Tier Benefits</h3>
        <ul class="space-y-2 text-sm text-gray-600">
            @switch($tier->value)
                @case(0)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Base points on every purchase (x1)
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Access to basic rewards
                    </li>
                    @break
                @case(1)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        x1.5 points multiplier
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        5% permanent discount
                    </li>
                    @break
                @case(2)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        x2 points multiplier
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        1 free month per year + priority support
                    </li>
                    @break
                @case(3)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        x3 points multiplier
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        20% discount + exclusive features + dedicated support
                    </li>
                    @break
            @endswitch
        </ul>

        @if($nextTier)
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs text-gray-500">Next tier ({{ $nextTier->label() }}): x{{ $nextTier->multiplier() }} multiplier and more benefits</p>
            </div>
        @endif
    </div>

    {{-- Section 3: Available Rewards --}}
    @if($availableRewards->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Available Rewards ({{ $availableRewards->count() }})</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($availableRewards as $reward)
                    <div class="border border-gray-200 rounded-xl p-4 text-center">
                        <p class="font-semibold text-gray-900 text-sm">{{ $reward->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $reward->points_cost }} pts</p>
                        @if($reward->description)
                            <p class="text-xs text-gray-400 mt-1">{{ $reward->description }}</p>
                        @endif
                        <form method="POST" action="{{ route('customer.loyalty.redeem', $reward) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white text-xs font-medium py-1.5 px-4 rounded-lg transition-colors"
                                    onclick="return confirm('Redeem {{ $reward->name }} for {{ $reward->points_cost }} points?')">
                                Redeem
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">Available Rewards</h3>
            <p class="text-sm text-gray-500 text-center py-4">You need more points to redeem rewards. Keep using the platform to earn more!</p>
        </div>
    @endif

    {{-- Section 4: Locked Rewards --}}
    @if($lockedRewards->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Locked Rewards</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($lockedRewards as $reward)
                    <div class="border border-gray-200 rounded-xl p-4 text-center opacity-60">
                        <div class="flex justify-center mb-1">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <p class="font-semibold text-gray-700 text-sm">{{ $reward->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $reward->points_cost }} pts</p>
                        @php
                            $pointsNeeded = max(0, $reward->points_cost - $customer->loyalty_points);
                        @endphp
                        @if($pointsNeeded > 0)
                            <p class="text-xs text-gray-400 mt-1">{{ number_format($pointsNeeded) }} more pts needed</p>
                        @endif
                        @if($reward->min_tier > $customer->loyalty_tier->value)
                            <p class="text-xs text-amber-500 mt-1">Requires {{ \App\Enums\LoyaltyTier::from($reward->min_tier)->label() }} tier</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Section 5: Transaction History --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Points History</h3>

        @if($transactions->isEmpty())
            <p class="text-sm text-gray-500 text-center py-8">No transactions yet. Start using the platform to earn points!</p>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-6 py-2 text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="text-left px-6 py-2 text-xs font-semibold text-gray-500 uppercase">Description</th>
                            <th class="text-right px-6 py-2 text-xs font-semibold text-gray-500 uppercase">Points</th>
                            <th class="text-right px-6 py-2 text-xs font-semibold text-gray-500 uppercase">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($transactions as $transaction)
                            <tr>
                                <td class="px-6 py-3 text-gray-600">{{ $transaction->created_at->format('d M Y') }}</td>
                                <td class="px-6 py-3 text-gray-900">{{ $transaction->description }}</td>
                                <td class="px-6 py-3 text-right font-semibold {{ $transaction->points >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->points >= 0 ? '+' : '' }}{{ $transaction->points }}
                                </td>
                                <td class="px-6 py-3 text-right text-gray-600">{{ number_format($transaction->balance_after) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Section 6: My Coupons --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">My Coupons</h3>

        @if($coupons->isEmpty())
            <p class="text-sm text-gray-500 text-center py-8">No coupons yet. Redeem rewards to get coupons!</p>
        @else
            <div class="space-y-3">
                @foreach($coupons as $coupon)
                    <div class="border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-mono font-bold text-gray-900">{{ $coupon->code }}</span>
                                <span class="text-gray-500 mx-2">&mdash;</span>
                                <span class="text-sm text-gray-700">{{ $coupon->reward?->name ?? $coupon->type }}</span>
                            </div>
                            <div>
                                @if($coupon->used_at)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500">
                                        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                        Used
                                    </span>
                                @elseif($coupon->expires_at->isPast())
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-red-500">
                                        <span class="w-2 h-2 rounded-full bg-red-400"></span>
                                        Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600">
                                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                        Available
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            @if($coupon->used_at)
                                Used on {{ $coupon->used_at->format('d M Y') }}
                                @if($coupon->order_id) &mdash; Order #{{ $coupon->order_id }} @endif
                            @else
                                Valid until {{ $coupon->expires_at->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Section 6: Referral Program --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Invite Friends</h3>
        <p class="text-sm text-gray-500 mb-4">Share your referral code and earn bonus points when your friends make their first purchase.</p>

        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1 bg-gray-50 rounded-lg px-4 py-3 font-mono text-lg font-bold text-center text-indigo-600 tracking-wider border-2 border-dashed border-indigo-200">
                {{ $customer->referral_code }}
            </div>
            <button
                onclick="navigator.clipboard.writeText('{{ $customer->referral_code }}').then(() => { this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy', 2000); })"
                class="px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium"
            >
                Copy
            </button>
        </div>

        @php
            $referralCount = $customer->referrals()->count();
        @endphp

        @if($referralCount > 0)
            <div class="text-sm text-gray-600">
                <span class="font-medium text-indigo-600">{{ $referralCount }}</span> friend{{ $referralCount === 1 ? '' : 's' }} referred
            </div>
        @else
            <div class="text-sm text-gray-400">No referrals yet. Share your code to get started!</div>
        @endif
    </div>
</div>
