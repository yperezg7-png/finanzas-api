<?php

namespace App\Http\Requests;

class AnioRequest extends PeriodoRequest
{
    public function rules(): array
    {
        return ['anio' => parent::rules()['anio']];
    }
}
