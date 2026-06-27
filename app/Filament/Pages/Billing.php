<?php

namespace App\Filament\Pages;

use App\Enums\CancellationReason;
use App\Enums\PlanType;
use App\Models\BillingEvent;
use App\Models\CancellationSurvey;
use App\Models\Plugin;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Enum;

class Billing extends Page
{
    protected static ?string $title = 'Billing & Plan';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Plan';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.billing';

    public bool $showCancelModal = false;

    public string $cancelReason = '';

    public string $cancelDetails = '';

    public static function canAccess(): bool
    {
        if (! hasModule('payments')) {
            return false;
        }

        $user = auth()->user();

        return $user && $user->can('billing.manage');
    }

    public function getTenant(): ?\App\Models\Tenant
    {
        return currentTenant();
    }

    public function getPlanConfig(): array
    {
        return config('saas.plans', []);
    }

    public function getCurrentPlan(): string
    {
        return $this->getTenant()?->plan ?? PlanType::default()->value;
    }

    public function getUsageData(): array
    {
        $tenant = $this->getTenant();

        if (! $tenant) {
            return [];
        }

        $limits = $tenant->planLimits();
        $usage = $tenant->usageCounts();
        $resources = ['orders', 'users', 'locations', 'items', 'customers'];
        $data = [];

        foreach ($resources as $resource) {
            $max = $limits[$resource] ?? null;
            $current = $usage[$resource] ?? 0;

            $data[$resource] = [
                'current' => $current,
                'max' => $max,
                'percentage' => $max ? (int) round($current / $max * 100) : null,
                'at_limit' => $tenant->isAtLimit($resource),
                'near_limit' => $tenant->isNearLimit($resource),
            ];
        }

        return $data;
    }

    public function getResourceLabels(): array
    {
        return [
            'orders' => 'Orders (this month)',
            'users' => 'Users',
            'locations' => 'Locations',
            'items' => 'Items',
            'customers' => 'Customers',
        ];
    }

    public function getReferralData(): array
    {
        $tenant = $this->getTenant();

        if (! $tenant) {
            return [];
        }

        $total = $tenant->referrals()->count();
        $converted = $tenant->referrals()->whereNotNull('converted_at')->count();

        return [
            'url' => $tenant->referral_url,
            'code' => $tenant->referral_code,
            'total' => $total,
            'converted' => $converted,
            'bonus_days' => $tenant->referral_bonus_days,
            'bonus_max' => config('saas.trial.referral_max_total_days', 120),
        ];
    }

    public function getPluginAddons(): array
    {
        $tenant = $this->getTenant();

        if (! $tenant) {
            return [];
        }

        $paidPlugins = $tenant->plugins()
            ->wherePivot('billing_type', 'paid')
            ->wherePivot('is_active', true)
            ->get();

        $addons = [];
        $totalMonthly = 0;

        /** @var Plugin $plugin */
        foreach ($paidPlugins as $plugin) {
            /** @var \App\Models\TenantPlugin|null $pivotData */
            $pivotData = $plugin->getRelation('pivot');
            $addons[] = [
                'name' => $plugin->name,
                'price' => $plugin->formattedPrice(),
                'price_monthly' => $plugin->price_monthly,
                'activated_at' => $pivotData?->activated_at,
            ];
            $totalMonthly += $plugin->price_monthly;
        }

        return [
            'addons' => $addons,
            'total_monthly' => $totalMonthly,
            'total_formatted' => '$' . number_format($totalMonthly / 100, 2) . '/mo',
        ];
    }

    /**
     * @return Collection<int, BillingEvent>
     */
    public function getBillingHistory(): Collection
    {
        $tenant = $this->getTenant();

        if (! $tenant) {
            return new Collection();
        }

        /** @var Collection<int, BillingEvent> */
        return $tenant->billingEvents()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function getNextBillingDate(): ?string
    {
        $tenant = $this->getTenant();

        if (! $tenant) {
            return null;
        }

        $subscription = $tenant->subscription('default');

        if (! $subscription || $subscription->canceled()) {
            return null;
        }

        if ($subscription->onTrial() && $subscription->trial_ends_at) {
            return $subscription->trial_ends_at->format('F j, Y');
        }

        $stripeSub = $subscription->asStripeSubscription();
        $anchor = $stripeSub->billing_cycle_anchor ?? null;

        if ($anchor) {
            $anchorDate = \Carbon\Carbon::createFromTimestamp($anchor);

            while ($anchorDate->isPast()) {
                $anchorDate->addMonth();
            }

            return $anchorDate->format('F j, Y');
        }

        return null;
    }

    public function getCancellationInfo(): ?array
    {
        $tenant = $this->getTenant();

        if (! $tenant) {
            return null;
        }

        $subscription = $tenant->subscription('default');

        if (! $subscription || ! $subscription->canceled()) {
            return null;
        }

        if (! $subscription->onGracePeriod()) {
            return null;
        }

        return [
            'plan' => $tenant->planLabel(),
            'ends_at' => $subscription->ends_at,
            'was_charged' => $subscription->stripe_status === 'active',
        ];
    }

    public function openCancelModal(): void
    {
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->cancelReason = '';
        $this->cancelDetails = '';
    }

    public function submitCancellation(): void
    {
        $this->validate([
            'cancelReason' => ['required', 'string', new Enum(CancellationReason::class)],
        ], [
            'cancelReason.required' => 'Please select a cancellation reason.',
            'cancelReason.in' => 'Please select a valid option.',
        ]);

        $tenant = $this->getTenant();

        if ($tenant) {
            CancellationSurvey::create([
                'tenant_id' => $tenant->id,
                'user_id' => auth()->id(),
                'reason' => $this->cancelReason,
                'details' => $this->cancelDetails ?: null,
            ]);
        }

        $this->redirect(route('billing.portal'));
    }
}
