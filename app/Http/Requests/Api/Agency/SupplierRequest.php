<?php

namespace App\Http\Requests\Api\Agency;

class SupplierRequest extends AgencyFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => "$required|string|max:255",
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'contactName' => 'sometimes|nullable|string|max:255',
            'category' => 'sometimes|nullable|string|max:100',
            'address' => 'sometimes|nullable|string|max:500',
            'website' => 'sometimes|nullable|string|max:255',
            'taxId' => 'sometimes|nullable|string|max:50',
            'notes' => 'sometimes|nullable|string',
            'isActive' => 'sometimes|boolean',
        ];
    }
}
