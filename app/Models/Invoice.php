<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'uuid',
        'number',
        'status',
        'customer_name',
        'customer_email',
        'customer_tax_id',
        'subtotal',
        'tax',
        'total',
        'pdf_path',
        'metadata',
        'issued_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
