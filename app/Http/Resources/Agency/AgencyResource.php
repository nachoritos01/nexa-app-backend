<?php

namespace App\Http\Resources\Agency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Generic resource for the English agency entities. Emits every model attribute
 * camelCased (e.g. client_id -> clientId, created_at -> createdAt) and drops the
 * internal tenant_id, matching the shape the panel's TypeScript types expect.
 * JSON columns (items, tasks, teamMembers, ...) are emitted as-is.
 */
class AgencyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $out = [];

        /** @var array<string, mixed> $attributes */
        $attributes = $this->resource->toArray();
        /** @var array<string, string> $casts */
        $casts = $this->resource->getCasts();

        foreach ($attributes as $key => $value) {
            if ($key === 'tenant_id') {
                continue;
            }
            // The panel types embedded collections (teamMembers, tasks, items, ...) as
            // non-null arrays, so emit [] for a null jsonb column instead of null.
            if ($value === null && ($casts[$key] ?? null) === 'array') {
                $value = [];
            }
            $out[Str::camel($key)] = $value;
        }

        return $out;
    }
}
