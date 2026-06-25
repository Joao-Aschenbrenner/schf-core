<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $expenseCategory = $this->route('expense_category');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('expense_categories', 'code')->ignore($expenseCategory)],
            'description' => ['sometimes', 'nullable', 'string'],
            'parent_id' => ['sometimes', 'nullable', 'exists:expense_categories,id'],
            'is_allowed_by_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
