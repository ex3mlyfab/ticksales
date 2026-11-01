<?php

namespace App\Http\Requests\Expense;

use App\Http\Requests\Concerns\ScopesValidationToTenant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
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
            'expense_category_id' => ['nullable', 'string', $this->existsInTenant('expense_categories')],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'expense_date' => ['required', 'date'],
            'payment_method_id' => ['nullable', 'string', $this->existsInTenant('payment_methods')],
            'note' => ['nullable', 'string'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
