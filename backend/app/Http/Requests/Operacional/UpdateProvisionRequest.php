<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProvisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'bank_account_id' => 'sometimes|nullable|exists:bank_accounts,id',
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'due_date' => 'sometimes|date',
            'provision_type' => 'sometimes|nullable|string|max:50',
            'notes' => 'sometimes|nullable|string|max:1000',
            'legacy_nota_id' => 'sometimes|nullable|integer|exists:historico_notas,id',
        ];
    }
}
