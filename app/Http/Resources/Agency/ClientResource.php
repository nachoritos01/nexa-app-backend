<?php

namespace App\Http\Resources\Agency;

use App\Models\Agency\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Client */
class ClientResource extends JsonResource
{
    /**
     * Emits the exact shape the panel's `Client` type expects (Spanish field names).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contactName' => $this->contact_name,
            'telefono' => $this->phone,
            'email' => $this->email,
            'sitioWeb' => $this->website,
            'industria' => $this->industry,
            'tipo' => $this->type,
            'origen' => $this->origin,
            'estado' => $this->status,
            'etapaPipeline' => $this->pipeline_stage,
            'notas' => $this->notes,
            'rating' => $this->rating,
            'valorPotencial' => $this->potential_value !== null ? (float) $this->potential_value : null,
            'logo' => $this->logo,
            'direccion' => $this->address,
            'rfc' => $this->tax_id,
            'fechaCreacion' => optional($this->created_at)->toISOString(),
            'fechaActualizacion' => optional($this->updated_at)->toISOString(),
        ];
    }
}
