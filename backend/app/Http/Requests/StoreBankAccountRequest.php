<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_code' => ['required', 'string', 'max:10'],
            'bank_name' => ['required', 'string', 'max:255'],
            'agency' => ['required', 'string', 'max:20'],
            'account' => ['required', 'string', 'max:30'],
            'digit' => ['nullable', 'string', 'max:5'],
            'type' => ['nullable', 'in:checking,savings,investment'],
            'holder_name' => ['nullable', 'string', 'max:255'],
            'holder_cnpj' => ['nullable', 'string', 'max:18'],
            'current_balance' => ['nullable', 'numeric'],
            'health_plan_id' => ['nullable', 'exists:health_plans,id'],
            'legacy_id' => ['nullable', 'integer'],
        ];
    }
}
