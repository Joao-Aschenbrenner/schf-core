<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_backups');
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:full,database,files',
            'password' => 'required|string|min:8|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Tipo de backup é obrigatório',
            'type.in' => 'Tipo deve ser: full, database ou files',
            'password.required' => 'Senha é obrigatória para criptografar o backup',
            'password.min' => 'Senha deve ter no mínimo 8 caracteres',
            'password.max' => 'Senha deve ter no máximo 100 caracteres',
        ];
    }
}