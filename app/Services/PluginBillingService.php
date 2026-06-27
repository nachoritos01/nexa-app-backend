<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\Tenant;
use App\Models\TenantPlugin;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;

class PluginBillingService
{
    /**
     * Activate a plugin for a tenant.
     * Free/included plugins are activated directly.
     * Paid plugins add a Stripe subscription item first.
     */
    public function activatePlugin(Tenant $tenant, Plugin $plugin): void
    {
        $billingType = $this->resolveBillingType($tenant, $plugin);

        if ($billingType === 'paid') {
            // If tenant has a Cashier subscription, go through Stripe
            // Otherwise (manual subscription), activate directly as paid
            if ($tenant->subscription('default')) {
                $this->addStripeSubscriptionItem($tenant, $plugin);
            } elseif ($tenant->subscribed_at !== null) {
                $tenant->plugins()->syncWithoutDetaching([
                    $plugin->id => [
                        'is_active' => true,
                        'activated_at' => now(),
                        'deactivated_at' => null,
                        'billing_type' => 'paid',
                    ],
                ]);
                $tenant->clearPluginCache();
            } else {
                throw new \RuntimeException('Tenant has no active subscription. Cannot add paid plugin.');
            }

            app(BillingHistoryService::class)->recordPluginActivated($tenant, $plugin);
        } else {
            $tenant->activatePlugin($plugin);
        }
    }

    /**
     * Deactivate a plugin for a tenant.
     * Paid plugins remove the Stripe subscription item first.
     */
    public function deactivatePlugin(Tenant $tenant, Plugin $plugin): void
    {
        /** @var TenantPlugin|null $pivot */
        $pivot = TenantPlugin::where('tenant_id', $tenant->id)
            ->where('plugin_id', $plugin->id)
            ->first();

        $wasPaid = $pivot && $pivot->billing_type === 'paid';

        if ($wasPaid && $pivot->stripe_subscription_item_id) {
            $this->removeStripeSubscriptionItem($tenant, $plugin, $pivot->stripe_subscription_item_id);
        } else {
            $tenant->deactivatePlugin($plugin);
        }

        if ($wasPaid) {
            app(BillingHistoryService::class)->recordPluginDeactivated($tenant, $plugin);
        }
    }

    /**
     * Sync plugin activations from the current Stripe subscription items.
     * Called from webhook when subscription changes externally.
     */
    public function syncPluginsFromSubscription(Tenant $tenant): void
    {
        $subscription = $tenant->subscription('default');

        if (! $subscription) {
            return;
        }

        $stripeSubscription = $subscription->asStripeSubscription();
        /** @var list<object> $stripeItems */
        $stripeItems = $stripeSubscription->items->data;
        $activeStripePriceIds = collect($stripeItems)
            ->pluck('price.id')
            ->all();

        // Get all paid plugins that have a stripe_price_id
        $paidPlugins = Plugin::whereNotNull('stripe_price_id')
            ->where('is_active', true)
            ->get();

        foreach ($paidPlugins as $plugin) {
            $isOnStripe = in_array($plugin->stripe_price_id, $activeStripePriceIds, true);
            $isActiveLocally = $tenant->hasPlugin($plugin->slug);

            if ($isOnStripe && ! $isActiveLocally) {
                $stripeItem = collect($stripeItems)
                    ->firstWhere('price.id', $plugin->stripe_price_id);

                $tenant->plugins()->syncWithoutDetaching([
                    $plugin->id => [
                        'is_active' => true,
                        'activated_at' => now(),
                        'deactivated_at' => null,
                        'billing_type' => 'paid',
                        'stripe_subscription_item_id' => $stripeItem->id ?? null,
                    ],
                ]);
            } elseif (! $isOnStripe && $isActiveLocally) {
                /** @var TenantPlugin|null $pivotRecord */
                $pivotRecord = TenantPlugin::where('tenant_id', $tenant->id)
                    ->where('plugin_id', $plugin->id)
                    ->first();

                if ($pivotRecord && $pivotRecord->billing_type === 'paid') {
                    $tenant->deactivatePlugin($plugin);
                }
            }
        }

        $tenant->clearPluginCache();
        hasModule(null); // Clear memoized cache
    }

    /**
     * Deactivate all paid plugins for a tenant (e.g. on subscription cancellation).
     */
    public function deactivateAllPaidPlugins(Tenant $tenant): void
    {
        $paidPluginIds = TenantPlugin::where('tenant_id', $tenant->id)
            ->where('billing_type', 'paid')
            ->where('is_active', true)
            ->pluck('plugin_id');

        $paidPlugins = Plugin::whereIn('id', $paidPluginIds)->get();

        foreach ($paidPlugins as $plugin) {
            $tenant->deactivatePlugin($plugin);
        }

        hasModule(null); // Clear memoized cache

        Log::info('Deactivated all paid plugins for tenant', [
            'tenant_id' => $tenant->id,
            'count' => $paidPlugins->count(),
        ]);
    }

    private function resolveBillingType(Tenant $tenant, Plugin $plugin): string
    {
        if ($plugin->isIncludedInPlan($tenant->plan)) {
            return 'included';
        }

        if ($plugin->is_free) {
            return 'free';
        }

        return 'paid';
    }

    private function addStripeSubscriptionItem(Tenant $tenant, Plugin $plugin): void
    {
        $subscription = $tenant->subscription('default');

        if (! $subscription) {
            throw new \RuntimeException('Tenant has no active subscription. Cannot add paid plugin.');
        }

        try {
            $subscription->addPriceAndInvoice($plugin->stripe_price_id);

            // Retrieve the newly added subscription item ID
            $stripeSubscription = $subscription->asStripeSubscription();
            /** @var list<object> $items */
            $items = $stripeSubscription->items->data;
            $stripeItem = collect($items)
                ->firstWhere('price.id', $plugin->stripe_price_id);

            $tenant->plugins()->syncWithoutDetaching([
                $plugin->id => [
                    'is_active' => true,
                    'activated_at' => now(),
                    'deactivated_at' => null,
                    'billing_type' => 'paid',
                    'stripe_subscription_item_id' => $stripeItem->id ?? null,
                ],
            ]);

            $tenant->clearPluginCache();
            hasModule(null); // Clear memoized cache

            Log::info('Paid plugin activated via Stripe', [
                'tenant_id' => $tenant->id,
                'plugin' => $plugin->slug,
                'stripe_item_id' => $stripeItem->id ?? null,
            ]);
        } catch (IncompletePayment $e) {
            Log::warning('Incomplete payment while activating plugin', [
                'tenant_id' => $tenant->id,
                'plugin' => $plugin->slug,
            ]);

            throw $e;
        }
    }

    private function removeStripeSubscriptionItem(Tenant $tenant, Plugin $plugin, string $subscriptionItemId): void
    {
        $subscription = $tenant->subscription('default');

        if (! $subscription) {
            $tenant->deactivatePlugin($plugin);

            return;
        }

        try {
            $subscription->removePrice($plugin->stripe_price_id);
        } catch (\Exception $e) {
            Log::warning('Failed to remove Stripe subscription item, deactivating locally', [
                'tenant_id' => $tenant->id,
                'plugin' => $plugin->slug,
                'error' => $e->getMessage(),
            ]);
        }

        $tenant->plugins()->updateExistingPivot($plugin->id, [
            'is_active' => false,
            'deactivated_at' => now(),
            'stripe_subscription_item_id' => null,
        ]);

        $tenant->clearPluginCache();
        hasModule(null); // Clear memoized cache

        Log::info('Paid plugin deactivated via Stripe', [
            'tenant_id' => $tenant->id,
            'plugin' => $plugin->slug,
        ]);
    }
}
