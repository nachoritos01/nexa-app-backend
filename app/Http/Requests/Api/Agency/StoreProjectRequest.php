<?php

namespace App\Http\Requests\Api\Agency;

class StoreProjectRequest extends AgencyFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'clientId' => 'sometimes|nullable|string',
            'type' => 'sometimes|nullable|string|max:50',
            'status' => 'sometimes|nullable|string|max:50',
            'priority' => 'sometimes|nullable|string|max:50',
            'budget' => 'sometimes|nullable|numeric',
            'startDate' => 'sometimes|nullable|string|max:30',
            'endDate' => 'sometimes|nullable|string|max:30',
            'teamMembers' => 'sometimes|array',
            'tasks' => 'sometimes|array',
            'progress' => 'sometimes|nullable|integer|min:0|max:100',
            'comments' => 'sometimes|array',
            'files' => 'sometimes|array',
        ];
    }
}
