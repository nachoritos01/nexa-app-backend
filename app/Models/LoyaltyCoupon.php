<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyCoupon extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'loyalty_reward_id',
        'code',
        'type',
        'value',
        'expires_at',
        'order_id',
        'used_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expires_at' => 'date',
        'used_at' => 'datetime',
    ];

    // Relationships

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class, 'loyalty_reward_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Helpers

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public static function generateCode(?string $prefix = null): string
    {
        $prefix = $prefix ?? config('business.code_prefix', 'APP');

        do {
            $code = $prefix . '-LY-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        } while (static::withoutGlobalScopes()->where('code', $code)->exists());

        return $code;
    }
}
