<?php

namespace App\Models;

use App\Enums\LoyaltyTier;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsActivityWithTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

/**
 * @property-read LoyaltyTier $loyalty_tier
 * @property-read string $loyalty_tier_name
 * @property-read float $loyalty_multiplier
 * @property-read string $initials
 */
class Customer extends Authenticatable
{
    use BelongsToTenant;
    use HasFactory;
    use LogsActivityWithTenant;

    protected static function booted(): void
    {
        static::creating(function (self $customer): void {
            if (! $customer->referral_code) {
                $customer->referral_code = self::generateReferralCode();
            }
        });
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (self::withoutGlobalScopes()->where('referral_code', $code)->exists());

        return $code;
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'notes',
        'password',
        'tags',
        'metadata',
        'referral_code',
        'referred_by',
        'birthday',
        'is_active',
        'loyalty_points',
        'loyalty_lifetime_points',
        'first_purchase_bonus',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'tags' => 'array',
        'metadata' => 'array',
        'birthday' => 'date',
        'is_active' => 'boolean',
        'loyalty_points' => 'integer',
        'loyalty_lifetime_points' => 'integer',
        'first_purchase_bonus' => 'boolean',
    ];

    // Relationships

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Order::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function loyaltyCoupons(): HasMany
    {
        return $this->hasMany(LoyaltyCoupon::class);
    }

    // Scopes

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('phone', 'ilike', "%{$term}%")
            ->orWhere('name', 'ilike', "%{$term}%");
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Accessors

    protected function loyaltyTier(): Attribute
    {
        return Attribute::get(fn (): LoyaltyTier => LoyaltyTier::fromPoints($this->loyalty_lifetime_points ?? 0));
    }

    protected function loyaltyTierName(): Attribute
    {
        return Attribute::get(fn (): string => $this->loyalty_tier->label());
    }

    protected function loyaltyMultiplier(): Attribute
    {
        return Attribute::get(fn (): float => $this->loyalty_tier->multiplier());
    }

    protected function initials(): Attribute
    {
        return Attribute::get(function (): string {
            $words = explode(' ', trim($this->name ?? ''));
            $initials = '';
            foreach (array_slice($words, 0, 2) as $word) {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            }

            return $initials ?: '?';
        });
    }
}
