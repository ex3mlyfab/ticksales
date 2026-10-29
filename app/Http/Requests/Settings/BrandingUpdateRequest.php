<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class BrandingUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo_path' => ['nullable', 'string', 'max:255'],
            'favicon_path' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'size:7'],
            'secondary_color' => ['nullable', 'string', 'size:7'],
            'accent_color' => ['nullable', 'string', 'size:7'],
            'sidebar_style' => ['required', 'string'],
            'border_radius' => ['required', 'string'],
            'theme_preset' => ['required', 'string'],
        ];
    }
}
