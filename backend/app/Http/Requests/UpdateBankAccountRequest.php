<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_code' => ['sometimes', 'string', 'max:10'],
            'bank_name' => ['sometimes', 'string', 'max:255'],
            'agency' => ['sometimes', 'string', 'max:20'],
            'account' => ['sometimes', 'string', 'max:30'],
            'digit' => ['sometimes', 'nullable', 'string', 'max:5'],
            'type' => ['sometimes', 'nullable', 'in:checking,savings,investment'],
            'holder_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'holder_cnpj' => ['sometimes', 'nullable', 'string', 'max:18'],
            'current_balance' => ['sometimes', 'nullable', 'numeric'],
            'health_plan_id' => ['sometimes', 'nullable', 'exists:health_plans,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
