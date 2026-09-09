<?php

namespace App\Http\Requests;

class StoreEgresoRequest extends EgresoRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reglasEgreso(['required']);
    }
}
