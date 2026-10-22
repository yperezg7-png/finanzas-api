<?php

namespace App\Http\Requests;

class UpdateIngresoRequest extends IngresoRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function authorize(): bool
    {
        abort_unless($this->route('ingreso')->user_id === $this->user()->id, 404);

        return true;
    }

    public function rules(): array
    {
        return $this->reglasIngreso(['sometimes', 'required']);
    }
}
