<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'cnpj' => ['sometimes', 'nullable', 'string', 'size:14', Rule::unique('suppliers', 'cnpj')->ignore($supplier)],
            'cpf' => ['sometimes', 'nullable', 'string', 'size:11', Rule::unique('suppliers', 'cpf')->ignore($supplier)],
            'trade_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ie' => ['sometimes', 'nullable', 'string', 'max:20'],
            'im' => ['sometimes', 'nullable', 'string', 'max:20'],
            'cnae' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'cellphone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address_complement' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_state' => ['sometimes', 'nullable', 'string', 'size:2'],
            'address_zip' => ['sometimes', 'nullable', 'string', 'max:9'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_agency' => ['sometimes', 'nullable', 'string', 'max:20'],
            'bank_account' => ['sometimes', 'nullable', 'string', 'max:30'],
            'bank_type' => ['sometimes', 'nullable', 'in:checking,savings'],
            'pix_key' => ['sometimes', 'nullable', 'string', 'max:100'],
            'pix_type' => ['sometimes', 'nullable', 'in:cpf,cnpj,email,phone,random'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
