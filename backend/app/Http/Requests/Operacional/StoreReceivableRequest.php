<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceivableRequest extends FormRequest
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
            'document_number' => 'nullable|string|max:50',
            'amount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'interest' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'payment_method' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ];
    }
}
