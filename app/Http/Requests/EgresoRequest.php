<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

abstract class EgresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @param  list<string>  $presencia
     * @return array<string, mixed>
     */
    protected function reglasEgreso(array $presencia): array
    {
        return [
            'fecha' => [...$presencia, 'date', 'before_or_equal:today'],
            'descripcion' => [...$presencia, 'string', 'max:150'],
            'categoria_id' => [
                ...$presencia,
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $existe = DB::table('categorias')
                        ->where('id', $value)
                        ->where('tipo', 'egreso')
                        ->where(function ($query) {
                            $query
                                ->whereNull('user_id')
                                ->orWhere('user_id', $this->user()->id);
                        })
                        ->exists();

                    if (! $existe) {
                        $fail('La categoría seleccionada no existe, no es de egreso o no pertenece a tu usuario.');
                    }
                },
            ],
            'subcategoria_id' => [
                'nullable',
                'integer',
                Rule::exists('subcategorias', 'id')
                    ->where('categoria_id', is_scalar($this->input('categoria_id')) ? $this->input('categoria_id') : 0),
            ],
            'monto' => [...$presencia, 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
            'notas' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe superar los 150 caracteres.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.integer' => 'La categoría seleccionada no es válida.',
            'subcategoria_id.integer' => 'La subcategoría seleccionada no es válida.',
            'subcategoria_id.exists' => 'La subcategoría seleccionada no existe o no pertenece a la categoría enviada.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.max' => 'El monto no puede superar 9,999,999,999.99.',
            'monto.numeric' => 'El monto debe ser numérico.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.decimal' => 'El monto debe tener como máximo 2 decimales.',
            'notas.string' => 'Las notas deben ser texto.',
        ];
    }
}
