<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUsage extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'feature',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function track(string $feature, ?int $userId = null): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        static::create([
            'tenant_id' => $tenant->id,
            'user_id' => $userId ?? auth()->id(),
            'feature' => $feature,
            'used_at' => now(),
        ]);
    }
}
