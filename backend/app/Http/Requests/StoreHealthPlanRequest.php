<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHealthPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:health_plans,code'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:sus,convenio,particular,emenda,municipal,outro'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'committed_balance' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'legacy_id' => ['nullable', 'integer'],
        ];
    }
}
