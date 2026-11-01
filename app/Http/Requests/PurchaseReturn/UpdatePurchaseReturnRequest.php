<?php

namespace App\Http\Requests\PurchaseReturn;

use App\Http\Requests\Concerns\ScopesValidationToTenant;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseReturnRequest extends FormRequest
{
    use ScopesValidationToTenant;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'string', 'exists:tenants,id'],
            'branch_id' => ['required', 'string', $this->existsInTenant('branches')],
            'supplier_id' => ['required', 'string', $this->existsInTenant('suppliers')],
            'purchase_id' => ['nullable', 'string', $this->existsInTenant('purchases')],
            'reason' => ['required', 'string'],
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', $this->existsInTenant('products')],
            'items.*.variant_id' => ['nullable', 'string', $this->existsInTenant('product_variants')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
