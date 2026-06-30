<?php

namespace App\Http\Requests\Api\Agency;

class QuoteRequest extends AgencyFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'number' => "$required|string|max:50",
            'clientId' => 'sometimes|nullable|string',
            'date' => 'sometimes|nullable|string|max:30',
            'validUntil' => 'sometimes|nullable|string|max:30',
            'status' => 'sometimes|nullable|string|max:50',
            'items' => 'sometimes|array',
            'discount' => 'sometimes|nullable|numeric',
            'tax' => 'sometimes|nullable|numeric',
            'subtotal' => 'sometimes|nullable|numeric',
            'total' => 'sometimes|nullable|numeric',
            'terms' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
        ];
    }
}
