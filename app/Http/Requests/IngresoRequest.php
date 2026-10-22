<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class IngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @param  list<string>  $presencia
     * @return array<string, mixed>
     */
    protected function reglasIngreso(array $presencia): array
    {
        return [
            'categoria_id' => [
                ...$presencia,
                'integer',
                Rule::exists('categorias', 'id')
                    ->where('tipo', 'ingreso')
                    ->where(function ($query) {
                        $query
                            ->whereNull('user_id')
                            ->orWhere('user_id', $this->user()->id);
                    }),
            ],
            'fecha' => [...$presencia, 'date'],
            'fuente' => [...$presencia, 'string', 'max:150'],
            'monto' => [...$presencia, 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
            'notas' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.integer' => 'La categoría debe ser un identificador entero.',
            'categoria_id.exists' => 'La categoría no existe, no es de ingreso o no pertenece a tu usuario.',
            'fecha.required' => 'La fecha es obligatoria.', 'fecha.date' => 'La fecha no es válida.',
            'fuente.required' => 'La fuente es obligatoria.', 'fuente.string' => 'La fuente debe ser texto.', 'fuente.max' => 'La fuente no debe superar 150 caracteres.',
            'monto.required' => 'El monto es obligatorio.', 'monto.numeric' => 'El monto debe ser numérico.', 'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.decimal' => 'El monto debe tener como máximo dos decimales.', 'monto.max' => 'El monto no puede superar 9,999,999,999.99.',
            'notas.string' => 'Las notas deben ser texto.',
        ];
    }
}
