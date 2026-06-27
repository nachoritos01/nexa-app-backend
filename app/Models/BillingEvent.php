<?php

namespace App\Models;

use App\Enums\BillingEventType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BillingEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'type',
        'description',
        'amount',
        'metadata',
    ];

    protected $casts = [
        'type' => BillingEventType::class,
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    public function formattedAmount(): ?string
    {
        if ($this->amount === null || $this->amount === 0) {
            return null;
        }

        return '$' . number_format($this->amount, 0) . '/mo';
    }
}
