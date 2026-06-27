<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'sometimes|nullable|email|max:255',
            'notes' => 'sometimes|nullable|string|max:5000',
            'order_lines' => 'sometimes|array',
            'order_lines.*.item_id' => 'required_with:order_lines|exists:items,id',
            'order_lines.*.description' => 'sometimes|nullable|string|max:255',
            'order_lines.*.quantity' => 'required_with:order_lines|integer|min:1',
            'order_lines.*.unit_price' => 'required_with:order_lines|numeric|min:0',
        ];
    }
}
