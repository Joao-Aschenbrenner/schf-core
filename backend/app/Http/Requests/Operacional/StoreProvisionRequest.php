<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class StoreProvisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'nullable|exists:suppliers,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'provision_type' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];
    }
}
