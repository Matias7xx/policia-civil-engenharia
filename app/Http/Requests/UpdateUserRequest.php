<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine se o usuário está autorizado a fazer esta solicitação.
     */
    public function authorize(): bool
    {
        return Auth::user()->isSuperAdmin;
    }

    /**
     * Obtenha as regras de validação que se aplicam à solicitação.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'matricula' => ['required', 'string', 'size:7', Rule::unique('users')->ignore($userId)],
            'cargo' => ['nullable', 'string', 'max:100'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'team_id' => ['required', 'exists:teams,id'],
            'role' => ['required', 'string', 'in:superadmin,admin,servidor'],
        ];
    }
}