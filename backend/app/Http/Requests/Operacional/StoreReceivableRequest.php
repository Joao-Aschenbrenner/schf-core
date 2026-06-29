<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceivableRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitize($this->all()));
    }

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

    private function sanitize(array $data): array
    {
        foreach (['description', 'document_number', 'payment_method', 'notes'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $clean = trim(str_ireplace('javascript:', '', strip_tags($data[$field])));
                $data[$field] = $clean !== '' ? $clean : 'sanitized';
            }
        }

        return $data;
    }
}
