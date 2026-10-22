<?php

namespace App\Support;

// Los importes permanecen como cadenas decimales; BCMath evita aritmética binaria.
final class Dinero
{
    public static function decimal(string|int $valor): string
    {
        return bcadd((string) $valor, '0', 2);
    }

    public static function resta(string $a, string $b): string
    {
        return bcsub($a, $b, 2);
    }

    public static function porcentaje(string $egresos, string $ingresos): string
    {
        if (bccomp($ingresos, '0', 2) <= 0) {
            return '0.00';
        }
        $valor = bcdiv(bcmul($egresos, '100', 2), $ingresos, 3);

        return bcadd($valor, '0.005', 2);
    }
}
