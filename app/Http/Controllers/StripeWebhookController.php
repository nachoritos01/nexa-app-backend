<?php

namespace App\Http\Controllers;

use App\Enums\PlanType;
use App\Enums\SubscriptionStatus;
use App\Events\PlanChanged;
use App\Models\Referral;
use App\Services\BillingHistoryService;
use App\Services\PluginBillingService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController;

class StripeWebhookController extends WebhookController
{
    public function __construct()
    {
        abort_unless(hasModule('payments'), 404);
    }

    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        $this->syncTenantPlan($payload);
        $this->processReferralReward($payload);
        $this->recordSubscriptionStarted($payload);

        return $response;
    }

    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $this->syncTenantPlan($payload);
        $this->syncTenantPlugins($payload);

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $stripeId = $payload['data']['object']['customer'] ?? null;
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $this->getUserByStripeId($stripeId);

        if ($tenant) {
            app(BillingHistoryService::class)->recordSubscriptionCancelled($tenant);

            $tenant->update([
                'plan' => PlanType::default()->value,
                'subscribed_at' => null,
            ]);

            app(PluginBillingService::class)->deactivateAllPaidPlugins($tenant);

            Log::info('Tenant subscription cancelled, reverted to starter', [
                'tenant_id' => $tenant->id,
            ]);
        }

        return $response;
    }

    protected function handleInvoicePaymentFailed(array $payload)
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        Log::warning('Stripe invoice payment failed', [
            'customer' => $stripeId,
            'invoice' => $payload['data']['object']['id'] ?? null,
        ]);

        return $this->successMethod();
    }

    private function syncTenantPlan(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $this->getUserByStripeId($stripeId);

        if (! $tenant) {
            return;
        }

        $priceId = $payload['data']['object']['items']['data'][0]['price']['id'] ?? null;
        $status = $payload['data']['object']['status'] ?? null;

        $subscriptionStatus = SubscriptionStatus::tryFrom($status ?? '');
        if (! $subscriptionStatus || ! $subscriptionStatus->isValid()) {
            return;
        }

        $plan = $this->resolvePlanFromPriceId($priceId);
        $previousPlan = $tenant->plan;

        $tenant->update([
            'plan' => $plan,
            'subscribed_at' => $tenant->subscribed_at ?? now(),
        ]);

        if ($previousPlan !== $plan) {
            PlanChanged::dispatch($tenant, $previousPlan, $plan);
        }

        Log::info('Tenant plan synced from Stripe', [
            'tenant_id' => $tenant->id,
            'plan' => $plan,
            'price_id' => $priceId,
        ]);
    }

    private function processReferralReward(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;
        $tenant = $this->getUserByStripeId($stripeId);

        if (! $tenant) {
            return;
        }

        $referral = Referral::where('referred_tenant_id', $tenant->id)
            ->whereNull('converted_at')
            ->first();

        if (! $referral) {
            return;
        }

        $referral->update(['converted_at' => now()]);

        /** @var \App\Models\Tenant|null $referrer */
        $referrer = $referral->referrer;

        if ($referrer) {
            $effectiveDays = $referrer->addReferralBonusDays(
                config('saas.trial.referral_bonus_conversion', 15),
                cap: config('saas.trial.referral_max_total_days', 120),
            );

            $referral->update(['rewarded_at' => now()]);

            Log::info('Referral conversion reward applied', [
                'referral_id' => $referral->id,
                'referrer_tenant_id' => $referral->referrer_tenant_id,
                'referred_tenant_id' => $referral->referred_tenant_id,
                'effective_days' => $effectiveDays,
            ]);
        }
    }

    private function syncTenantPlugins(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $this->getUserByStripeId($stripeId);

        if (! $tenant) {
            return;
        }

        try {
            app(PluginBillingService::class)->syncPluginsFromSubscription($tenant);
        } catch (\Exception $e) {
            Log::warning('Failed to sync plugins from subscription', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function recordSubscriptionStarted(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        /** @var \App\Models\Tenant|null $tenant */
        $tenant = $this->getUserByStripeId($stripeId);

        if (! $tenant) {
            return;
        }

        app(BillingHistoryService::class)->recordSubscriptionStarted($tenant, $tenant->plan);
    }

    private function resolvePlanFromPriceId(?string $priceId): string
    {
        if (! $priceId) {
            return PlanType::default()->value;
        }

        $plans = config('saas.plans', []);

        foreach (PlanType::cases() as $planType) {
            $plan = $plans[$planType->value] ?? [];

            if (($plan['stripe_monthly'] ?? null) === $priceId || ($plan['stripe_yearly'] ?? null) === $priceId) {
                return $planType->value;
            }
        }

        return PlanType::default()->value;
    }
}
