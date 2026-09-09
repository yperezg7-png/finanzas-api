<?php

namespace App\Http\Requests;

class UpdateIngresoRequest extends IngresoRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reglasIngreso(['sometimes', 'required']);
    }
}
