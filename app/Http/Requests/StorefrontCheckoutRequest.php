<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorefrontCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'fulfillment_method' => ['required', Rule::in(['pickup', 'local_delivery'])],
            'delivery_address' => [
                Rule::requiredIf($this->input('fulfillment_method') === 'local_delivery'),
                'nullable',
                'string',
                'max:2000',
            ],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'paystack'])],
            'idempotency_key' => ['required', 'uuid'],
            'tenant_id' => ['prohibited'],
            'branch_id' => ['prohibited'],
            'items' => ['prohibited'],
            'subtotal' => ['prohibited'],
            'tax_amount' => ['prohibited'],
            'delivery_fee' => ['prohibited'],
            'total_amount' => ['prohibited'],
            'tracking_token_hash' => ['prohibited'],
        ];
    }
}
