<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Egreso;
use App\Models\Ingreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function resumen(Request $request): JsonResponse
    {
        $datos = $this->validarPeriodo($request);

        $anio = (int) $datos['anio'];
        $mes = (int) $datos['mes'];
        $userId = $request->user()->id;

        $ingresos = $this->sumasPorMesYAcumulado(Ingreso::query(), $userId, $anio, $mes);
        $egresos = $this->sumasPorMesYAcumulado(Egreso::query(), $userId, $anio, $mes);

        $ingresosMes = $this->decimal($ingresos->mes);
        $egresosMes = $this->decimal($egresos->mes);
        $ingresosAcumulados = $this->decimal($ingresos->acumulado);
        $egresosAcumulados = $this->decimal($egresos->acumulado);

        return response()->json([
            'ingresos_mes' => $ingresosMes,
            'egresos_mes' => $egresosMes,
            'balance_mes' => $this->decimal($ingresosMes - $egresosMes),
            'ingresos_acumulados' => $ingresosAcumulados,
            'egresos_acumulados' => $egresosAcumulados,
            'balance_acumulado' => $this->decimal($ingresosAcumulados - $egresosAcumulados),
            'porcentaje_gastado' => $ingresosMes > 0
                ? $this->decimal(($egresosMes / $ingresosMes) * 100)
                : 0.0,
        ]);
    }

    public function egresosPorCategoria(Request $request): JsonResponse
    {
        $datos = $this->validarPeriodo($request);
        $anio = (int) $datos['anio'];
        $mes = (int) $datos['mes'];

        $egresos = Egreso::query()
            ->join('categorias', 'categorias.id', '=', 'egresos.categoria_id')
            ->where('egresos.user_id', $request->user()->id)
            ->whereYear('egresos.fecha', $anio)
            ->whereMonth('egresos.fecha', $mes)
            ->groupBy('categorias.id', 'categorias.nombre')
            ->selectRaw('categorias.id as categoria_id, categorias.nombre, COALESCE(SUM(egresos.monto), 0) as total')
            ->orderByDesc('total')
            ->get()
            ->map(function ($categoria) {
                $categoria->total = $this->decimal($categoria->total);

                return $categoria;
            });

        return response()->json($egresos);
    }

    public function resumenAnual(Request $request): JsonResponse
    {
        $datos = $this->validarAnio($request);
        $anio = (int) $datos['anio'];
        $userId = $request->user()->id;

        $ingresosPorMes = $this->sumasAnualesPorMes(Ingreso::query(), $userId, $anio);
        $egresosPorMes = $this->sumasAnualesPorMes(Egreso::query(), $userId, $anio);

        $resumen = collect(range(1, 12))
            ->map(function (int $mes) use ($ingresosPorMes, $egresosPorMes) {
                $ingresos = $this->decimal($ingresosPorMes->get($mes, 0));
                $egresos = $this->decimal($egresosPorMes->get($mes, 0));

                return [
                    'mes' => $mes,
                    'ingresos' => $ingresos,
                    'egresos' => $egresos,
                    'balance' => $this->decimal($ingresos - $egresos),
                ];
            })
            ->values();

        return response()->json($resumen);
    }

    private function sumasPorMesYAcumulado($query, int $userId, int $anio, int $mes): object
    {
        return $query
            ->where('user_id', $userId)
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', '<=', $mes)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN MONTH(fecha) = ? THEN monto ELSE 0 END), 0) as mes,
                COALESCE(SUM(monto), 0) as acumulado',
                [$mes]
            )
            ->first();
    }

    private function sumasAnualesPorMes($query, int $userId, int $anio)
    {
        return $query
            ->where('user_id', $userId)
            ->whereYear('fecha', $anio)
            ->selectRaw('MONTH(fecha) as mes, COALESCE(SUM(monto), 0) as total')
            ->groupByRaw('MONTH(fecha)')
            ->pluck('total', 'mes');
    }

    private function decimal(mixed $valor): float
    {
        return round((float) $valor, 2);
    }

    /**
     * @return array{anio: int|string, mes: int|string}
     */
    private function validarPeriodo(Request $request): array
    {
        return $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['required', 'integer', 'between:1,12'],
        ], [
            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.min' => 'El año no es válido.',
            'anio.max' => 'El año no es válido.',
            'mes.required' => 'El mes es obligatorio.',
            'mes.integer' => 'El mes debe ser un número entero.',
            'mes.between' => 'El mes debe estar entre 1 y 12.',
        ]);
    }

    /**
     * @return array{anio: int|string}
     */
    private function validarAnio(Request $request): array
    {
        return $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
        ], [
            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.min' => 'El año no es válido.',
            'anio.max' => 'El año no es válido.',
        ]);
    }
}
