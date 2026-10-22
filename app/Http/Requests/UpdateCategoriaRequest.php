<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless($this->route('categoria')->user_id === $this->user()->id, 404);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareForValidation(): void
    {
        $categoria = $this->route('categoria');
        if ($categoria && $categoria->user_id === $this->user()->id) {
            $this->merge(['nombre' => $this->input('nombre', $categoria->nombre), 'tipo' => $this->input('tipo', $categoria->tipo)]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => ['bail', 'sometimes', 'required', 'string', 'max:80', Rule::unique('categorias', 'nombre')->where('user_id', $this->user()->id)->where('tipo', is_string($this->input('tipo')) ? $this->input('tipo') : '')->ignore($this->route('categoria')?->id)],
            'tipo' => ['sometimes', 'required', Rule::in(['ingreso', 'egreso'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.string' => 'El nombre de la categoría debe ser texto.',
            'nombre.unique' => 'Ya tienes una categoría con ese nombre y tipo.',
            'nombre.max' => 'El nombre de la categoría no debe superar los 80 caracteres.',
            'tipo.required' => 'El tipo de categoría es obligatorio.',
            'tipo.in' => 'El tipo de categoría debe ser ingreso o egreso.',
        ];
    }
}
