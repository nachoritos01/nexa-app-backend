<?php

namespace App\Http\Requests\Api\Agency;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contactName' => 'sometimes|nullable|string|max:255',
            'telefono' => 'sometimes|nullable|string|max:50',
            'email' => 'sometimes|nullable|email|max:255',
            'sitioWeb' => 'sometimes|nullable|string|max:255',
            'industria' => 'sometimes|nullable|string|max:255',
            'tipo' => 'sometimes|nullable|string|max:50',
            'origen' => 'sometimes|nullable|string|max:50',
            'estado' => 'sometimes|nullable|string|max:50',
            'etapaPipeline' => 'sometimes|nullable|string|max:50',
            'notas' => 'sometimes|nullable|string|max:5000',
            'rating' => 'sometimes|nullable|integer|min:0|max:5',
            'valorPotencial' => 'sometimes|nullable|numeric|min:0',
            'logo' => 'sometimes|nullable|string',
            'direccion' => 'sometimes|nullable|string|max:500',
            'rfc' => 'sometimes|nullable|string|max:50',
        ];
    }

    /**
     * Map the panel's (Spanish) field names to the table columns.
     *
     * @return array<string, mixed>
     */
    public function mapped(): array
    {
        $v = $this->validated();
        $map = [
            'name' => 'name',
            'contactName' => 'contact_name',
            'telefono' => 'phone',
            'email' => 'email',
            'sitioWeb' => 'website',
            'industria' => 'industry',
            'tipo' => 'type',
            'origen' => 'origin',
            'estado' => 'status',
            'etapaPipeline' => 'pipeline_stage',
            'notas' => 'notes',
            'rating' => 'rating',
            'valorPotencial' => 'potential_value',
            'logo' => 'logo',
            'direccion' => 'address',
            'rfc' => 'tax_id',
        ];

        $columns = [];
        foreach ($map as $field => $column) {
            if (array_key_exists($field, $v)) {
                $columns[$column] = $v[$field];
            }
        }

        return $columns;
    }
}
