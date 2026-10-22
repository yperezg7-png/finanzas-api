<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeriodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'anio' => ['required', 'integer', 'between:2000,2100'],
            'mes' => ['required', 'integer', 'between:1,12'],
        ];
    }

    public function messages(): array
    {
        return ['anio.required' => 'El año es obligatorio.', 'anio.integer' => 'El año debe ser entero.', 'anio.between' => 'El año debe estar entre 2000 y 2100.',
            'mes.required' => 'El mes es obligatorio.', 'mes.integer' => 'El mes debe ser entero.', 'mes.between' => 'El mes debe estar entre 1 y 12.'];
    }
}
