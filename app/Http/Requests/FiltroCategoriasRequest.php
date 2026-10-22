<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltroCategoriasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['tipo' => ['sometimes', Rule::in(['ingreso', 'egreso'])]];
    }

    public function messages(): array
    {
        return ['tipo.in' => 'El tipo de categoría debe ser ingreso o egreso.'];
    }
}
