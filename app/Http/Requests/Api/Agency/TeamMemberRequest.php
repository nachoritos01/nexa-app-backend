<?php

namespace App\Http\Requests\Api\Agency;

class TeamMemberRequest extends AgencyFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => "$required|string|max:255",
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'role' => 'sometimes|nullable|string|max:50',
            'department' => 'sometimes|nullable|string|max:100',
            'salary' => 'sometimes|nullable|numeric',
            'avatar' => 'sometimes|nullable|string',
            'isActive' => 'sometimes|boolean',
        ];
    }
}
