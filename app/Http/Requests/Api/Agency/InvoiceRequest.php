<?php

namespace App\Http\Requests\Api\Agency;

class InvoiceRequest extends AgencyFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'number' => "$required|string|max:50",
            'clientId' => 'sometimes|nullable|string',
            'projectId' => 'sometimes|nullable|string',
            'quoteId' => 'sometimes|nullable|string',
            'date' => 'sometimes|nullable|string|max:30',
            'dueDate' => 'sometimes|nullable|string|max:30',
            'status' => 'sometimes|nullable|string|max:50',
            'items' => 'sometimes|array',
            'subtotal' => 'sometimes|nullable|numeric',
            'tax' => 'sometimes|nullable|numeric',
            'total' => 'sometimes|nullable|numeric',
            'notes' => 'sometimes|nullable|string',
        ];
    }
}
