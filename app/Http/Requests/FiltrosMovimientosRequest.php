<?php

namespace App\Http\Requests;

class FiltrosMovimientosRequest extends PeriodoRequest
{
    public function rules(): array
    {
        return ['anio' => ['sometimes', 'integer', 'between:2000,2100'], 'mes' => ['sometimes', 'integer', 'between:1,12']];
    }
}
