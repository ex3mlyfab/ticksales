<?php

namespace App\Http\Requests\Settings;

use App\Models\TenantBranding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandingUpdateRequest extends FormRequest
{
    /**
     * Tenant branding is limited to identity (logo, favicon) and one
     * permitted accent from the curated list. Foundational design tokens
     * — colours, sidebar styling, radius and theme presets — are
     * platform-owned and cannot be modified by tenants.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo_path' => ['nullable', 'string', 'max:255'],
            'favicon_path' => ['nullable', 'string', 'max:255'],
            'accent_color' => [
                'nullable',
                'string',
                Rule::in(TenantBranding::permittedAccents()),
            ],
        ];
    }

    /**
     * Normalise accent hex values so a permitted accent can be sent in
     * either case (e.g. #279c78) while still matching the curated list.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($accent = $this->input('accent_color'))) {
            $this->merge(['accent_color' => strtoupper(trim($accent))]);
        }
    }
}
