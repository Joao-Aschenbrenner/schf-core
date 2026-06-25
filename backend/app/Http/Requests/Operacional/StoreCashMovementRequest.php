<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_register_id' => 'required|exists:cash_registers,id',
            'type' => 'required|string|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'document' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'payable_id' => 'nullable|exists:payables,id',
            'receivable_id' => 'nullable|exists:receivables,id',
        ];
    }
}
