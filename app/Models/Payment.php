<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Events\PaymentReceived;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'amount',
        'method',
        'reference',
        'gateway_id',
        'payment_type',
        'notes',
        'metadata',
        'received_at',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_at' => 'datetime',
        'metadata' => 'array',
        'method' => PaymentMethod::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    protected static function booted(): void
    {
        static::saved(function (self $payment) {
            $order = $payment->order;
            $order->syncTotalPaid();

            if ($order->status === \App\Enums\OrderStatus::Draft && $order->total_paid > 0) {
                $order->confirm();
            }

            PaymentReceived::dispatch($payment);
        });

        static::deleted(function (self $payment) {
            $payment->order->syncTotalPaid();
        });
    }
}
