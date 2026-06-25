<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'register_date' => 'required|date|unique:cash_registers,register_date',
            'opening_balance' => 'required|numeric',
            'operator' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];
    }
}
