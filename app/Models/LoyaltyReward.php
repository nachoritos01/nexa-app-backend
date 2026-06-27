<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyReward extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'type',
        'points_cost',
        'value',
        'icon',
        'min_tier',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'points_cost' => 'integer',
        'value' => 'decimal:2',
        'min_tier' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailableFor(Builder $query, Customer $customer): Builder
    {
        return $query->where('is_active', true)
            ->where('points_cost', '<=', $customer->loyalty_points)
            ->where('min_tier', '<=', $customer->loyalty_tier->value);
    }
}
