<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'sometimes|string|max:255',
            'bank_account_id' => 'sometimes|nullable|exists:bank_accounts,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'discount' => 'sometimes|nullable|numeric|min:0',
            'due_date' => 'sometimes|date',
            'payment_method' => 'sometimes|nullable|string|max:50',
            'bar_code' => 'sometimes|nullable|string|max:48',
            'payment_line_code' => 'sometimes|nullable|string|max:54',
            'notes' => 'sometimes|nullable|string|max:500',
        ];
    }
}
