<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $tenant_id
 * @property int $plugin_id
 * @property bool $is_active
 * @property \Carbon\Carbon|null $activated_at
 * @property \Carbon\Carbon|null $deactivated_at
 * @property string $billing_type
 * @property string|null $stripe_subscription_item_id
 * @property array|null $metadata
 */
class TenantPlugin extends Pivot
{
    protected $table = 'tenant_plugins';

    protected $casts = [
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }
}
