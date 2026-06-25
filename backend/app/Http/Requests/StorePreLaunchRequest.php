<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePreLaunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255',
            'type' => 'required|in:debit,credit',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'health_plan_id' => 'nullable|exists:health_plans,id',
            'resource_plan_id' => 'nullable|exists:resource_plans,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'estimated_amount' => 'required|numeric|min:0',
            'expected_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
