<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

class LoyaltyService
{
    /**
     * @return array{points_per_amount: int, first_purchase_bonus: int, referral_bonus: int, birthday_bonus: int, coupon_validity_days: int}
     */
    private function getConfig(Tenant $tenant): array
    {
        $settings = $tenant->settings ?? [];
        $loyalty = $settings['loyalty'] ?? [];

        return [
            'points_per_amount' => (int) ($loyalty['points_per_amount'] ?? 10),
            'first_purchase_bonus' => (int) ($loyalty['first_purchase_bonus'] ?? 50),
            'referral_bonus' => (int) ($loyalty['referral_bonus'] ?? 100),
            'birthday_bonus' => (int) ($loyalty['birthday_bonus'] ?? 25),
            'coupon_validity_days' => (int) ($loyalty['coupon_validity_days'] ?? 90),
        ];
    }

    public function creditTransactionPoints(Order $order): void
    {
        /** @var Customer|null $customer */
        $customer = $order->customer;
        if (! $customer) {
            return;
        }

        $amount = (int) $order->total;
        $points = $this->calculatePoints($amount, $customer);

        if ($points > 0) {
            $this->recordTransaction(
                $customer,
                'credit',
                $points,
                'Order #' . $order->id . ' completed',
                $order,
            );
        }

        if (! $customer->first_purchase_bonus) {
            $this->creditFirstPurchaseBonus($customer);
            $this->creditReferrerIfApplicable($customer);
        }
    }

    public function reverseTransactionPoints(Order $order): void
    {
        /** @var Customer|null $customer */
        $customer = $order->customer;
        if (! $customer) {
            return;
        }

        /** @var LoyaltyTransaction|null $existingTransaction */
        $existingTransaction = LoyaltyTransaction::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'credit')
            ->first();

        if (! $existingTransaction) {
            return;
        }

        $this->recordTransaction(
            $customer,
            'reversal',
            -abs($existingTransaction->points),
            'Order #' . $order->id . ' cancelled (reversal)',
            $order,
        );
    }

    public function creditFirstPurchaseBonus(Customer $customer): void
    {
        /** @var Tenant|null $tenant */
        $tenant = $customer->tenant;
        if (! $tenant) {
            return;
        }

        $config = $this->getConfig($tenant);
        $bonus = $config['first_purchase_bonus'];

        if ($bonus > 0) {
            $this->recordTransaction(
                $customer,
                'bonus',
                $bonus,
                'First purchase bonus',
            );
        }

        $customer->update(['first_purchase_bonus' => true]);
    }

    public function redeemReward(Customer $customer, LoyaltyReward $reward): LoyaltyCoupon
    {
        /** @var Tenant $tenant */
        $tenant = $customer->tenant;
        $config = $this->getConfig($tenant);

        $this->recordTransaction(
            $customer,
            'debit',
            -$reward->points_cost,
            'Redeemed: ' . $reward->name,
            $reward,
        );

        /** @var LoyaltyCoupon $coupon */
        $coupon = LoyaltyCoupon::create([
            'customer_id' => $customer->id,
            'loyalty_reward_id' => $reward->id,
            'code' => LoyaltyCoupon::generateCode(),
            'type' => $reward->type,
            'value' => $reward->value,
            'expires_at' => now()->addDays($config['coupon_validity_days']),
        ]);

        return $coupon;
    }

    public function creditReferralBonus(Customer $referrer, Customer $referred): void
    {
        /** @var Tenant|null $tenant */
        $tenant = $referrer->tenant;
        if (! $tenant) {
            return;
        }

        $config = $this->getConfig($tenant);
        $bonus = $config['referral_bonus'];

        if ($bonus > 0) {
            $this->recordTransaction(
                $referrer,
                'bonus',
                $bonus,
                'Referral bonus — ' . $referred->name,
            );
        }
    }

    public function calculatePoints(int $amount, Customer $customer): int
    {
        /** @var Tenant|null $tenant */
        $tenant = $customer->tenant;
        if (! $tenant) {
            return 0;
        }

        $config = $this->getConfig($tenant);
        $base = intdiv($amount, $config['points_per_amount']);

        return (int) floor($base * $customer->loyalty_multiplier);
    }

    private function creditReferrerIfApplicable(Customer $customer): void
    {
        if (! $customer->referred_by) {
            return;
        }

        /** @var Customer|null $referrer */
        $referrer = Customer::withoutGlobalScopes()->find($customer->referred_by);

        if ($referrer) {
            $this->creditReferralBonus($referrer, $customer);
        }
    }

    private function recordTransaction(
        Customer $customer,
        string $type,
        int $points,
        string $description,
        ?Model $reference = null,
    ): LoyaltyTransaction {
        $newBalance = max(0, $customer->loyalty_points + $points);

        /** @var LoyaltyTransaction $transaction */
        $transaction = LoyaltyTransaction::create([
            'customer_id' => $customer->id,
            'type' => $type,
            'points' => $points,
            'balance_after' => $newBalance,
            'description' => $description,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
        ]);

        $updateData = ['loyalty_points' => $newBalance];

        if ($points > 0) {
            $updateData['loyalty_lifetime_points'] = $customer->loyalty_lifetime_points + $points;
        }

        $customer->update($updateData);

        return $transaction;
    }
}
