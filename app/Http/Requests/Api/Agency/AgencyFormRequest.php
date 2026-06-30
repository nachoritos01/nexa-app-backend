<?php

namespace App\Http\Requests\Api\Agency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Base request for the English agency entities. Subclasses declare camelCase
 * validation rules; mapped() converts the validated payload to snake_case
 * columns (clientId -> client_id, teamMembers -> team_members, ...).
 */
abstract class AgencyFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validated payload with keys converted to snake_case columns.
     *
     * @return array<string, mixed>
     */
    public function mapped(): array
    {
        $out = [];
        foreach ($this->validated() as $key => $value) {
            $out[Str::snake($key)] = $value;
        }

        return $out;
    }
}
