<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNfeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nfe_key' => 'required|string|max:44|unique:nfe,nfe_key',
            'nfe_number' => 'required|string|max:20',
            'serie' => 'nullable|string|max:10',
            'emission_date' => 'required|date',
            'entry_date' => 'nullable|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'health_plan_id' => 'nullable|exists:health_plans,id',
            'resource_plan_id' => 'nullable|exists:resource_plans,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'goods_value' => 'nullable|numeric|min:0',
            'service_value' => 'nullable|numeric|min:0',
            'insurance_value' => 'nullable|numeric|min:0',
            'other_value' => 'nullable|numeric|min:0',
            'icms_value' => 'nullable|numeric|min:0',
            'ipi_value' => 'nullable|numeric|min:0',
            'pis_value' => 'nullable|numeric|min:0',
            'cofins_value' => 'nullable|numeric|min:0',
            'total_value' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'xml_content' => 'nullable|string',
            'is_manual_entry' => 'boolean',
            'items' => 'nullable|array',
            'items.*.code' => 'nullable|string|max:50',
            'items.*.description' => 'required|string|max:255',
            'items.*.ncm' => 'nullable|string|max:10',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:10',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
        ];
    }
}
