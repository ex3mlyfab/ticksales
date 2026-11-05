<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\Concerns\ScopesValidationToTenant;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    use ScopesValidationToTenant;

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
        $productId = $this->route('product')?->id;
        $tenantId = $this->attributes->get('tenant')?->id;

        $uniqueInTenant = fn () => Rule::unique('products')
            ->where(fn ($query) => $query->where('tenant_id', $tenantId))
            ->ignore($productId);

        return [
            'tenant_id' => ['nullable', 'string', 'exists:tenants,id'],
            'category_id' => ['nullable', 'string', $this->existsInTenant('product_categories')],
            'brand_id' => ['nullable', 'string', $this->existsInTenant('product_brands')],
            'unit_id' => ['nullable', 'string', $this->existsInTenant('product_units')],
            'tax_rate_id' => ['nullable', 'string', $this->existsInTenant('tax_rates')],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50', $uniqueInTenant()],
            'barcode' => ['nullable', 'string', 'max:100', $uniqueInTenant()],
            'description' => [
                Rule::when((bool) $this->boolean('is_storefront_published'), ['required', 'string']),
                'nullable',
                'string',
            ],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => [
                'required',
                'numeric',
                Rule::when(
                    (bool) $this->boolean('is_storefront_published') && ! $this->boolean('has_variants'),
                    ['gt:0'],
                ),
                'min:0',
            ],
            'has_variants' => ['required', 'boolean'],
            'track_inventory' => ['required', 'boolean'],
            'alert_quantity' => ['required', 'integer', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_storefront_published' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['description', 'selling_price', 'has_variants'])) {
                    return;
                }

                if (! $this->boolean('is_storefront_published')) {
                    return;
                }

                $product = $this->route('product');
                if (! $product instanceof Product) {
                    return;
                }

                if (! $this->boolean('has_variants')) {
                    return;
                }

                $activeVariants = $product->variants()->where('is_active', true)->get(['name', 'selling_price']);
                if (
                    $activeVariants->isEmpty()
                    || $activeVariants->contains(fn ($variant): bool => ! filled($variant->name)
                        || $variant->selling_price === null
                        || (float) $variant->selling_price <= 0)
                ) {
                    $validator->errors()->add(
                        'is_storefront_published',
                        'Variant products need at least one valid active variant before publication; every active variant needs a name and price.',
                    );
                }
            },
        ];
    }
}
