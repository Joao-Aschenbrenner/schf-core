<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'description' => 'required|string|max:255',
            'investment_type' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0.01',
            'yield_rate' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'maturity_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|string|in:active,redeemed,closed',
            'legacy_conta_id' => 'nullable|integer|exists:historico_contas,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
