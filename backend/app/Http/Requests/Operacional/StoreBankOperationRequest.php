<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'type' => 'required|string|in:credit,debit,investment,transfer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'document' => 'nullable|string|max:50',
            'operation_date' => 'required|date',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string|max:255',
            'payable_id' => 'nullable|exists:payables,id',
            'receivable_id' => 'nullable|exists:receivables,id',
            'bank_investment_id' => 'nullable|exists:bank_investments,id',
        ];
    }
}
