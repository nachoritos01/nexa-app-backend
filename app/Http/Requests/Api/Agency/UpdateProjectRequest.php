<?php

namespace App\Http\Requests\Api\Agency;

class UpdateProjectRequest extends StoreProjectRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = 'sometimes|string|max:255';

        return $rules;
    }
}
