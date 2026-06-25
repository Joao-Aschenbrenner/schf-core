<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255',
            'document_number' => 'nullable|string|max:50',
            'supplier_id' => 'required|exists:suppliers,id',
            'nfe_id' => 'nullable|exists:nfe,id',
            'health_plan_id' => 'nullable|exists:health_plans,id',
            'resource_plan_id' => 'nullable|exists:resource_plans,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'discount' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'bar_code' => 'nullable|string|max:48',
            'payment_line_code' => 'nullable|string|max:54',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
