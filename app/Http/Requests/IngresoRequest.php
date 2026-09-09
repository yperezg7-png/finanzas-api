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
            'monto' => [...$presencia, 'numeric', 'min:0.01'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
