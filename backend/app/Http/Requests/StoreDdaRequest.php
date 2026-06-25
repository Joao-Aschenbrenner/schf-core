<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDdaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_code' => 'required|string|max:10',
            'bank_name' => 'nullable|string|max:100',
            'document_number' => 'nullable|string|max:50',
            'title_number' => 'nullable|string|max:50',
            'bar_code' => 'required|string|max:48|unique:ddas,bar_code',
            'payment_line_code' => 'nullable|string|max:54',
            'payer_name' => 'nullable|string|max:200',
            'payer_cnpj' => 'nullable|string|max:18',
            'payer_cpf' => 'nullable|string|max:14',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'nfe_id' => 'nullable|exists:nfe,id',
            'payable_id' => 'nullable|exists:payables,id',
            'notes' => 'nullable|string|max:500',
            'raw_data' => 'nullable|array',
        ];
    }
}
