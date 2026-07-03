<?php

namespace App\Http\Requests\Api\Agency;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Settings are stored as a single JSON blob in the panel's (camelCase) shape,
     * so the keys are kept as-is rather than mapped to columns.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'companyName' => 'sometimes|nullable|string|max:255',
            'companyEmail' => 'sometimes|nullable|string|max:255',
            'companyPhone' => 'sometimes|nullable|string|max:50',
            'companyAddress' => 'sometimes|nullable|string|max:500',
            'companyWebsite' => 'sometimes|nullable|string|max:255',
            'taxId' => 'sometimes|nullable|string|max:50',
            'logo' => 'sometimes|nullable|string',
            'defaultTax' => 'sometimes|nullable|numeric',
            'defaultPaymentTerms' => 'sometimes|nullable|numeric',
            'invoicePrefix' => 'sometimes|nullable|string|max:20',
            'quotePrefix' => 'sometimes|nullable|string|max:20',
            'invoiceNotes' => 'sometimes|nullable|string',
            'quoteTerms' => 'sometimes|nullable|string',
            'emailNotifications' => 'sometimes|boolean',
            'projectUpdates' => 'sometimes|boolean',
            'invoiceReminders' => 'sometimes|boolean',
            'quoteExpiry' => 'sometimes|boolean',
            'weeklyReports' => 'sometimes|boolean',
            'theme' => 'sometimes|nullable|string|max:20',
            'language' => 'sometimes|nullable|string|max:10',
            'dateFormat' => 'sometimes|nullable|string|max:20',
            'currency' => 'sometimes|nullable|string|max:10',
        ];
    }
}
