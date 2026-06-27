<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property bool $is_enabled_for_tenant
 * @property bool $is_included
 */
class Plugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'category',
        'is_active',
        'is_free',
        'price_monthly',
        'stripe_price_id',
        'included_in_plans',
        'required_modules',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_free' => 'boolean',
        'price_monthly' => 'integer',
        'included_in_plans' => 'array',
        'required_modules' => 'array',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_plugins')
            ->withPivot(['is_active', 'activated_at', 'deactivated_at', 'billing_type', 'stripe_subscription_item_id', 'metadata'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailableForPlan(Builder $query, string $plan): Builder
    {
        return $query->where('is_active', true)
            ->whereJsonContains('included_in_plans', $plan);
    }

    public function isIncludedInPlan(string $plan): bool
    {
        return in_array($plan, $this->included_in_plans ?? [], true);
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Check if the global module flag is enabled
        $modules = $this->required_modules ?? [];

        foreach ($modules as $module) {
            if (! config("modules.{$module}", false)) {
                return false;
            }
        }

        return true;
    }

    public function formattedPrice(): string
    {
        if ($this->is_free || $this->price_monthly === 0) {
            return 'Free';
        }

        return '$' . number_format($this->price_monthly / 100, 2) . '/mo';
    }
}
