<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHealthPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $healthPlan = $this->route('health_plan');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('health_plans', 'code')->ignore($healthPlan)],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'in:sus,convenio,particular,emenda,municipal,outro'],
            'balance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'committed_balance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
