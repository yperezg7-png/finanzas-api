<?php

namespace App\Http\Requests;

class RegisterRequest extends LoginRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:8']];
    }

    public function messages(): array
    {
        return [...parent::messages(), 'name.required' => 'El nombre es obligatorio.', 'name.string' => 'El nombre debe ser texto.', 'name.max' => 'El nombre no debe superar 255 caracteres.',
            'email.unique' => 'El correo ya está registrado.', 'password.min' => 'La contraseña debe tener al menos 8 caracteres.'];
    }
}
