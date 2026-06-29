<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'size:14', 'unique:suppliers,cnpj'],
            'cpf' => ['nullable', 'string', 'size:11', 'unique:suppliers,cpf'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'ie' => ['nullable', 'string', 'max:20'],
            'im' => ['nullable', 'string', 'max:20'],
            'cnae' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cellphone' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:100'],
            'address_district' => ['nullable', 'string', 'max:100'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'address_state' => ['nullable', 'string', 'size:2'],
            'address_zip' => ['nullable', 'string', 'max:9'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_agency' => ['nullable', 'string', 'max:20'],
            'bank_account' => ['nullable', 'string', 'max:30'],
            'bank_type' => ['nullable', 'in:checking,savings'],
            'pix_key' => ['nullable', 'string', 'max:100'],
            'pix_type' => ['nullable', 'in:cpf,cnpj,email,phone,random'],
            'notes' => ['nullable', 'string'],
            'legacy_id' => ['nullable', 'integer'],
        ];
    }

    private function sanitize(array $data): array
    {
        foreach (['name', 'trade_name', 'contact_name', 'notes'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $clean = trim(str_ireplace('javascript:', '', strip_tags($data[$field])));
                $data[$field] = $clean !== '' ? $clean : 'sanitized';
            }
        }

        return $data;
    }
}
