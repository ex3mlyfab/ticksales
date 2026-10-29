<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'category_id' => ['nullable', 'string', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'string', 'exists:product_brands,id'],
            'unit_id' => ['nullable', 'string', 'exists:product_units,id'],
            'tax_rate_id' => ['nullable', 'string', 'exists:tax_rates,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku,NULL,id,tenant_id'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode,NULL,id,tenant_id'],
            'description' => ['nullable', 'string'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'has_variants' => ['required', 'boolean'],
            'track_inventory' => ['required', 'boolean'],
            'alert_quantity' => ['required', 'integer', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
