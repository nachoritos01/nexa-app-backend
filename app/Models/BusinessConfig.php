<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BusinessConfig extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    // Static Methods
    public static function get(string $key, mixed $default = null): mixed
    {
        $tenantId = currentTenant()?->id ?? 'global';
        $config = Cache::remember("business_config.{$tenantId}.{$key}", 3600, function () use ($key) {
            return self::active()->where('key', $key)->first();
        });

        if (! $config) {
            return $default;
        }

        return self::castValue($config->value, $config->type);
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): self
    {
        $tenantId = currentTenant()?->id ?? 'global';
        Cache::forget("business_config.{$tenantId}.{$key}");

        return self::updateOrCreate(
            ['tenant_id' => currentTenant()?->id, 'key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
                'group' => $group,
            ]
        );
    }

    public static function getGroup(string $group): array
    {
        return self::active()
            ->byGroup($group)
            ->get()
            ->mapWithKeys(function ($config) {
                return [$config->key => self::castValue($config->value, $config->type)];
            })
            ->toArray();
    }

    protected static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true),
            'float' => (float) $value,
            default => $value,
        };
    }
}
