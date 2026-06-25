<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'sometimes|string|max:255',
            'investment_type' => 'sometimes|string|max:50',
            'yield_rate' => 'sometimes|nullable|numeric|min:0',
            'maturity_date' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
