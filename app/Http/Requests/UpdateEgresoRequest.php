<?php

namespace App\Http\Requests;

class UpdateEgresoRequest extends EgresoRequest
{
    public function authorize(): bool
    {
        abort_unless($this->route('egreso')->user_id === $this->user()->id, 404);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $egreso = $this->route('egreso');
        if (! $egreso || $egreso->user_id !== $this->user()->id) {
            return;
        }
        // Validar siempre el par efectivo. Si cambia la categoría sin indicar subcategoría, limpiarla.
        $categoria = $this->input('categoria_id', $egreso->categoria_id);
        $subcategoria = $this->exists('subcategoria_id') ? $this->input('subcategoria_id')
            : ((string) $categoria === (string) $egreso->categoria_id ? $egreso->subcategoria_id : null);
        $this->merge(['categoria_id' => $categoria, 'subcategoria_id' => $subcategoria]);
    }

    public function rules(): array
    {
        return $this->reglasEgreso(['sometimes', 'required']);
    }
}
