<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use Billable;
    use HasEncryptedSettings;
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'referral_code',
        'owner_id',
        'settings',
        'is_active',
        'trial_ends_at',
        'subscribed_at',
        'onboarding_steps',
        'onboarding_completed_at',
        'referral_bonus_days',
        'health_score',
        'health_score_calculated_at',
        'stripe_id',
        'pm_type',
        'pm_last_four',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'onboarding_steps' => 'array',
        'onboarding_completed_at' => 'datetime',
        'referral_bonus_days' => 'integer',
        'health_score' => 'integer',
        'health_score_calculated_at' => 'datetime',
    ];

    // Relationships

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function featureUsages(): HasMany
    {
        return $this->hasMany(FeatureUsage::class);
    }

    public function cancellationSurveys(): HasMany
    {
        return $this->hasMany(CancellationSurvey::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_tenant_id');
    }

    public function referredBy(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_tenant_id');
    }

    public function billingEvents(): HasMany
    {
        return $this->hasMany(BillingEvent::class);
    }

    public function plugins(): BelongsToMany
    {
        return $this->belongsToMany(Plugin::class, 'tenant_plugins')
            ->using(TenantPlugin::class)
            ->withPivot(['is_active', 'activated_at', 'deactivated_at', 'billing_type', 'stripe_subscription_item_id', 'metadata'])
            ->withTimestamps();
    }

    public function hasPlugin(string $slug): bool
    {
        $cacheKey = "tenant:{$this->id}:plugins";

        $activeSlugs = Cache::remember($cacheKey, 300, function () {
            return $this->plugins()
                ->wherePivot('is_active', true)
                ->pluck('slug')
                ->all();
        });

        return in_array($slug, $activeSlugs, true);
    }

    public function activatePlugin(Plugin $plugin): void
    {
        $this->plugins()->syncWithoutDetaching([
            $plugin->id => [
                'is_active' => true,
                'activated_at' => now(),
                'deactivated_at' => null,
                'billing_type' => $plugin->isIncludedInPlan($this->plan) ? 'included' : ($plugin->is_free ? 'free' : 'paid'),
            ],
        ]);

        $this->clearPluginCache();
    }

    public function deactivatePlugin(Plugin $plugin): void
    {
        $this->plugins()->updateExistingPivot($plugin->id, [
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        $this->clearPluginCache();
    }

    public function clearPluginCache(): void
    {
        Cache::forget("tenant:{$this->id}:plugins");
    }

    // Referral helpers

    public function getReferralUrlAttribute(): string
    {
        return url('/admin/register?ref=' . $this->referral_code);
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function addReferralBonusDays(int $days, int $cap): int
    {
        $available = max(0, $cap - $this->referral_bonus_days);
        $effective = min($days, $available);

        if ($effective <= 0) {
            return 0;
        }

        $this->increment('referral_bonus_days', $effective);

        // Only extend trial if not subscribed
        if (! $this->isSubscribed()) {
            if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) {
                // Trial active: extend from current end
                $this->update(['trial_ends_at' => $this->trial_ends_at->addDays($effective)]);
            } else {
                // Trial expired or null: extend from now
                $this->update(['trial_ends_at' => now()->addDays($effective)]);
            }
        }

        return $effective;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tenant $tenant): void {
            if (empty($tenant->referral_code)) {
                $tenant->referral_code = static::generateReferralCode();
            }
        });
    }

    // Trial helpers

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null
            && $this->subscribed_at === null
            && $this->trial_ends_at->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        if (! $this->trial_ends_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at !== null
            && $this->subscribed_at === null
            && $this->trial_ends_at->isPast();
    }

    public function isInGracePeriod(): bool
    {
        if (! $this->isTrialExpired()) {
            return false;
        }

        $graceDays = config('saas.trial.grace_days', 3);

        return $this->trial_ends_at->addDays($graceDays)->isFuture();
    }

    public function trialColorStatus(): string
    {
        $days = $this->trialDaysRemaining();

        if ($this->isTrialExpired()) {
            return 'danger';
        }

        if ($days <= 3) {
            return 'danger';
        }

        if ($days <= 7) {
            return 'warning';
        }

        return 'success';
    }

    public function isSuspended(): bool
    {
        return $this->isTrialExpired()
            && ! $this->isInGracePeriod()
            && ! $this->isSubscribed();
    }

    public function suspend(): void
    {
        $this->update(['is_active' => false]);

        \App\Events\TenantSuspended::dispatch($this);
    }

    // Onboarding helpers

    public function isOnboardingComplete(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function markOnboardingStep(string $step): void
    {
        $steps = $this->onboarding_steps ?? [];
        $steps[$step] = now()->toISOString();
        $this->update(['onboarding_steps' => $steps]);
    }

    public function hasCompletedOnboardingStep(string $step): bool
    {
        return isset($this->onboarding_steps[$step]);
    }

    public function onboardingProgress(): int
    {
        $totalSteps = count(config('saas.onboarding.steps', []));

        if ($totalSteps === 0) {
            return 100;
        }

        $completed = count($this->onboarding_steps ?? []);

        return (int) round(($completed / $totalSteps) * 100);
    }

    // Plan & subscription helpers

    public function isSubscribed(): bool
    {
        return $this->subscribed_at !== null || $this->subscribed('default');
    }

    public function planLabel(): string
    {
        return config("saas.plans.{$this->plan}.label", ucfirst($this->plan));
    }

    public function planLimits(): array
    {
        $plan = config("saas.plans.{$this->plan}", []);

        return [
            'orders' => $plan['max_orders'] ?? null,
            'users' => $plan['max_users'] ?? null,
            'locations' => $plan['max_locations'] ?? null,
            'items' => $plan['max_items'] ?? null,
            'customers' => $plan['max_customers'] ?? null,
        ];
    }

    public function clearUsageCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget("tenant:{$this->id}:usage_counts");
    }

    public function usageCounts(): array
    {
        $cacheKey = "tenant:{$this->id}:usage_counts";
        $ttl = config('saas.cache.usage_counts_ttl', 300);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttl, function () {
            // Single query for users/locations/items/customers counts
            $counts = \Illuminate\Support\Facades\DB::selectOne(
                'SELECT
                    (SELECT COUNT(*) FROM tenant_user WHERE tenant_id = ?) as users_count,
                    (SELECT COUNT(*) FROM locations WHERE tenant_id = ?) as locations_count,
                    (SELECT COUNT(*) FROM items WHERE tenant_id = ?) as items_count,
                    (SELECT COUNT(*) FROM customers WHERE tenant_id = ?) as customers_count',
                [$this->id, $this->id, $this->id, $this->id]
            );

            return [
                'orders' => $this->orders()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'users' => (int) $counts->users_count,
                'locations' => (int) $counts->locations_count,
                'items' => (int) $counts->items_count,
                'customers' => (int) $counts->customers_count,
            ];
        });
    }

    public function isAtLimit(string $resource): bool
    {
        $limits = $this->planLimits();
        $max = $limits[$resource] ?? null;

        if ($max === null) {
            return false;
        }

        $usage = $this->usageCounts();

        return ($usage[$resource] ?? 0) >= $max;
    }

    public function isNearLimit(string $resource): bool
    {
        $limits = $this->planLimits();
        $max = $limits[$resource] ?? null;

        if ($max === null) {
            return false;
        }

        $usage = $this->usageCounts();
        $percentage = config('saas.limits.soft_limit_percentage', 80);

        return ($usage[$resource] ?? 0) >= ($max * $percentage / 100);
    }

    public function usagePercentage(string $resource): ?int
    {
        $limits = $this->planLimits();
        $max = $limits[$resource] ?? null;

        if ($max === null) {
            return null;
        }

        $usage = $this->usageCounts();

        return (int) round(($usage[$resource] ?? 0) / $max * 100);
    }

    // Health score helpers

    public function healthColor(): string
    {
        if ($this->health_score === null) {
            return 'gray';
        }

        $healthy = config('saas.retention.health_score.healthy_threshold', 70);
        $atRisk = config('saas.retention.health_score.at_risk_threshold', 50);

        return match (true) {
            $this->health_score >= $healthy => 'success',
            $this->health_score >= $atRisk => 'warning',
            default => 'danger',
        };
    }

    public function healthLabel(): string
    {
        if ($this->health_score === null) {
            return 'N/A';
        }

        $healthy = config('saas.retention.health_score.healthy_threshold', 70);
        $atRisk = config('saas.retention.health_score.at_risk_threshold', 50);

        return match (true) {
            $this->health_score >= $healthy => 'Healthy',
            $this->health_score >= $atRisk => 'At risk',
            default => 'Critical',
        };
    }

    // Slug generation

    public static function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
