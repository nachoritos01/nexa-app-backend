<?php

namespace App\Http\Requests\Api\Agency;

class ExpenseRequest extends AgencyFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'description' => "$required|string|max:1000",
            'amount' => 'sometimes|nullable|numeric',
            'category' => 'sometimes|nullable|string|max:50',
            'date' => 'sometimes|nullable|string|max:30',
            'status' => 'sometimes|nullable|string|max:50',
            'supplierId' => 'sometimes|nullable|string',
            'projectId' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
        ];
    }
}
