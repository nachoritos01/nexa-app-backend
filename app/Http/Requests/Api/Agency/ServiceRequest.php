<?php

namespace App\Http\Requests\Api\Agency;

class ServiceRequest extends AgencyFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => "$required|string|max:255",
            'description' => 'sometimes|nullable|string',
            'category' => 'sometimes|nullable|string|max:50',
            'basePrice' => 'sometimes|nullable|numeric',
            'estimatedHours' => 'sometimes|nullable|integer|min:0',
            'isActive' => 'sometimes|boolean',
        ];
    }
}
