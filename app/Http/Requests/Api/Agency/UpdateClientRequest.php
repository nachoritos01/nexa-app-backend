<?php

namespace App\Http\Requests\Api\Agency;

class UpdateClientRequest extends StoreClientRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = 'sometimes|string|max:255';

        return $rules;
    }
}
