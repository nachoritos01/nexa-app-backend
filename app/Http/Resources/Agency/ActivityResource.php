<?php

namespace App\Http\Resources\Agency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * Serializes a spatie Activity row for the panel timeline (camelCase). `subjectType`
 * is the short class name (e.g. "Quote"); `changes` exposes the dirty attributes and
 * their previous values.
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'event' => $this->event,
            'subjectType' => $this->subject_type ? class_basename($this->subject_type) : null,
            'subjectId' => $this->subject_id,
            'causerName' => $this->causer?->getAttribute('name'),
            'changes' => [
                'attributes' => $this->properties->get('attributes'),
                'old' => $this->properties->get('old'),
            ],
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
