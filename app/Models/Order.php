<?php

namespace App\Models;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsActivityWithTenant;
use App\Services\LoyaltyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use LogsActivityWithTenant;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::updated(function (self $order): void {
            if (! hasModule('loyalty') || ! $order->isDirty('status')) {
                return;
            }

            $loyaltyService = app(LoyaltyService::class);

            if ($order->status === OrderStatus::Completed) {
                $loyaltyService->creditTransactionPoints($order);
            }

            if (
                $order->status === OrderStatus::Cancelled
                && $order->getOriginal('status') === OrderStatus::Completed->value
            ) {
                $loyaltyService->reverseTransactionPoints($order);
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'location_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'status',
        'priority',
        'subtotal',
        'discount_amount',
        'tax',
        'total',
        'total_paid',
        'paid_at',
        'notes',
        'attachments',
        'metadata',
        'estimated_at',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'paid_at' => 'datetime',
        'attachments' => 'array',
        'metadata' => 'array',
        'estimated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'status' => OrderStatus::class,
        'priority' => OrderPriority::class,
    ];

    // Relationships

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            OrderStatus::Completed,
            OrderStatus::Cancelled,
        ]);
    }

    // Accessors

    public function getLineCountAttribute(): int
    {
        return $this->lines()->sum('quantity');
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->total ?? 0) - (float) ($this->total_paid ?? 0);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof OrderStatus
            ? $this->status->label()
            : (string) $this->status;
    }

    // State transitions

    public function confirm(): self
    {
        $this->status = OrderStatus::Confirmed;
        $this->confirmed_at = now();
        $this->save();

        return $this;
    }

    public function complete(): self
    {
        $this->status = OrderStatus::Completed;
        $this->completed_at = now();
        $this->save();

        return $this;
    }

    public function cancel(): self
    {
        $this->status = OrderStatus::Cancelled;
        $this->cancelled_at = now();
        $this->save();

        return $this;
    }

    // Helpers

    public function recalculateTotals(): self
    {
        $subtotal = $this->lines()->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total = max(0, $subtotal + (float) ($this->tax ?? 0) - (float) ($this->discount_amount ?? 0));
        $this->save();

        return $this;
    }

    public function syncTotalPaid(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->total_paid = $totalPaid;
        $this->paid_at = ($this->total > 0 && $totalPaid >= $this->total) ? now() : null;
        $this->saveQuietly();
    }

    public function duplicate(): self
    {
        $newOrder = self::create([
            'customer_id' => $this->customer_id,
            'location_id' => $this->location_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'notes' => $this->notes,
            'attachments' => $this->attachments,
            'metadata' => $this->metadata,
            'status' => OrderStatus::Draft,
            'subtotal' => 0,
            'total' => 0,
            'total_paid' => 0,
        ]);

        foreach ($this->lines as $line) {
            $newOrder->lines()->create([
                'item_id' => $line->item_id,
                'description' => $line->description,
                'variant' => $line->variant,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'subtotal' => $line->subtotal,
                'metadata' => $line->metadata,
            ]);
        }

        $newOrder->recalculateTotals();

        return $newOrder;
    }
}
