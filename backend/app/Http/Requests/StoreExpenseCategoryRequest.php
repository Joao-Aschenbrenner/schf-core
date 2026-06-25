<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:expense_categories,code'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:expense_categories,id'],
            'is_allowed_by_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'legacy_id' => ['nullable', 'integer'],
        ];
    }
}
