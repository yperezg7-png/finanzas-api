<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string']];
    }

    public function messages(): array
    {
        return ['email.required' => 'El correo es obligatorio.', 'email.email' => 'El correo no es válido.', 'email.max' => 'El correo no debe superar 255 caracteres.',
            'password.required' => 'La contraseña es obligatoria.', 'password.string' => 'La contraseña debe ser texto.'];
    }
}
