<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNfeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'health_plan_id' => 'sometimes|exists:health_plans,id',
            'resource_plan_id' => 'sometimes|nullable|exists:resource_plans,id',
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'description' => 'sometimes|nullable|string|max:500',
        ];
    }
}
