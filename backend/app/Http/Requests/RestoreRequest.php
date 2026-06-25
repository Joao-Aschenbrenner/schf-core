<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_backups');
    }

    public function rules(): array
    {
        return [
            'password' => 'required|string|min:8|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Senha é obrigatória para restaurar backup',
            'password.min' => 'Senha deve ter no mínimo 8 caracteres',
            'password.max' => 'Senha deve ter no máximo 100 caracteres',
        ];
    }
}