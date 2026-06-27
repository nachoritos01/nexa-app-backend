<?php

namespace App\Http\Controllers;

use App\Enums\BillingPeriod;
use App\Enums\PlanType;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class BillingController extends Controller
{
    public function __construct(
        private BillingService $billingService,
    ) {
        abort_unless(hasModule('payments'), 404);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => ['required', new Enum(PlanType::class)],
            'period' => ['required', new Enum(BillingPeriod::class)],
        ]);

        $tenant = currentTenant();

        if (! $tenant) {
            abort(403);
        }

        $plan = $request->input('plan');
        $period = BillingPeriod::from($request->input('period'));
        $priceId = config("saas.plans.{$plan}.{$period->stripeKey()}");

        if (! $priceId) {
            return redirect()->route('filament.admin.pages.billing')
                ->with('error', 'Plan not available. Please configure Stripe price IDs.');
        }

        // Already subscribed → swap plan
        if ($tenant->subscribed('default')) {
            $error = $this->billingService->swapPlan($tenant, $plan, $priceId);

            if ($error) {
                return redirect()->route('filament.admin.pages.billing')
                    ->with('error', $error);
            }

            return redirect()->route('filament.admin.pages.billing')
                ->with('success', 'Plan updated successfully.');
        }

        // First subscription → Stripe Checkout
        $checkout = $tenant->newSubscription('default', $priceId)
            ->allowPromotionCodes();

        // Preserve remaining trial days
        if ($tenant->isOnTrial() && $tenant->trial_ends_at) {
            $checkout->trialUntil($tenant->trial_ends_at);
        }

        return $checkout->checkout([
            'success_url' => route('filament.admin.pages.billing') . '?checkout=success',
            'cancel_url' => route('filament.admin.pages.billing') . '?checkout=cancelled',
        ]);
    }

    public function portal()
    {
        $tenant = currentTenant();

        if (! $tenant || ! $tenant->stripe_id) {
            return redirect()->route('filament.admin.pages.billing');
        }

        return $tenant->redirectToBillingPortal(
            route('filament.admin.pages.billing')
        );
    }
}
