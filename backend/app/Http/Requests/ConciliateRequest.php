<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read int $statement_item_id
 * @property-read int $payable_id
 */
class ConciliateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statement_item_id' => 'required|exists:bank_statement_items,id',
            'payable_id' => 'required|exists:payables,id',
        ];
    }
}
