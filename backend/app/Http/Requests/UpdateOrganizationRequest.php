<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->route('organization')?->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'cnpj' => 'nullable|string|max:18|unique:organizations,cnpj,' . $organizationId,
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'logo_path' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'settings' => 'nullable|array',
        ];
    }
}