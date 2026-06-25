<?php

namespace App\Http\Requests\Operacional;

use Illuminate\Foundation\Http\FormRequest;

class RedeemBankInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'redeemed_amount' => 'sometimes|numeric|min:0',
            'redeemed_at' => 'sometimes|date',
        ];
    }
}
