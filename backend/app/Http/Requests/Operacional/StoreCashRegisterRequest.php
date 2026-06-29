<?php

namespace App\Http\Requests\Operacional;

use App\Models\Operacional\CashRegister;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'register_date' => 'required|date',
            'opening_balance' => 'required|numeric',
            'operator' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $date = $this->input('register_date');

            if ($date && CashRegister::whereDate('register_date', $date)->exists()) {
                $validator->errors()->add('register_date', 'The register date has already been taken.');
            }
        });
    }
}
