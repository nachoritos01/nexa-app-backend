<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsActivityWithTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use LogsActivityWithTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'category',
        'sku',
        'price',
        'variants',
        'photos',
        'tags',
        'metadata',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'variants' => 'array',
        'photos' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            if (is_array($item->photos)) {
                $item->photos = array_values($item->photos);
            }
        });
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    // Accessors

    public function getPhotoUrlsAttribute(): array
    {
        return array_map(
            fn (string $path) => Storage::disk('public')->url($path),
            $this->photos ?? [],
        );
    }

    public function getMainPhotoAttribute(): ?string
    {
        $path = $this->photos[0] ?? null;

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
