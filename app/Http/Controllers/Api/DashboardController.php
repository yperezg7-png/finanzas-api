<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnioRequest;
use App\Http\Requests\PeriodoRequest;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Support\Dinero;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function resumen(PeriodoRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $anio = (int) $datos['anio'];
        $mes = (int) $datos['mes'];
        $userId = $request->user()->id;

        $ingresos = $this->sumasPorMesYAcumulado(Ingreso::query(), $userId, $anio, $mes);
        $egresos = $this->sumasPorMesYAcumulado(Egreso::query(), $userId, $anio, $mes);

        $ingresosMes = Dinero::decimal($ingresos->mes);
        $egresosMes = Dinero::decimal($egresos->mes);
        $ingresosAcumulados = Dinero::decimal($ingresos->acumulado);
        $egresosAcumulados = Dinero::decimal($egresos->acumulado);

        return response()->json([
            'ingresos_mes' => $ingresosMes,
            'egresos_mes' => $egresosMes,
            'balance_mes' => Dinero::resta($ingresosMes, $egresosMes),
            'ingresos_acumulados' => $ingresosAcumulados,
            'egresos_acumulados' => $egresosAcumulados,
            'balance_acumulado' => Dinero::resta($ingresosAcumulados, $egresosAcumulados),
            'porcentaje_gastado' => Dinero::porcentaje($egresosMes, $ingresosMes),
        ]);
    }

    public function egresosPorCategoria(PeriodoRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $anio = (int) $datos['anio'];
        $mes = (int) $datos['mes'];

        $egresos = Egreso::query()
            ->join('categorias', 'categorias.id', '=', 'egresos.categoria_id')
            ->where('egresos.user_id', $request->user()->id)
            ->whereYear('egresos.fecha', $anio)
            ->whereMonth('egresos.fecha', $mes)
            ->groupBy('categorias.id', 'categorias.nombre')
            ->selectRaw('categorias.id as categoria_id, categorias.nombre, CAST(COALESCE(SUM(egresos.monto), 0) AS CHAR) as total')
            ->orderByRaw('SUM(egresos.monto) DESC')
            ->get()
            ->map(function ($categoria) {
                $categoria->total = Dinero::decimal($categoria->total);

                return $categoria;
            });

        return response()->json($egresos);
    }

    public function resumenAnual(AnioRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $anio = (int) $datos['anio'];
        $userId = $request->user()->id;

        $ingresosPorMes = $this->sumasAnualesPorMes(Ingreso::query(), $userId, $anio);
        $egresosPorMes = $this->sumasAnualesPorMes(Egreso::query(), $userId, $anio);

        $resumen = collect(range(1, 12))
            ->map(function (int $mes) use ($ingresosPorMes, $egresosPorMes) {
                $ingresos = Dinero::decimal($ingresosPorMes->get($mes, 0));
                $egresos = Dinero::decimal($egresosPorMes->get($mes, 0));

                return [
                    'mes' => $mes,
                    'ingresos' => $ingresos,
                    'egresos' => $egresos,
                    'balance' => Dinero::resta($ingresos, $egresos),
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
                'CAST(COALESCE(SUM(CASE WHEN MONTH(fecha) = ? THEN monto ELSE 0 END), 0) AS CHAR) as mes,
                CAST(COALESCE(SUM(monto), 0) AS CHAR) as acumulado',
                [$mes]
            )
            ->first();
    }

    private function sumasAnualesPorMes($query, int $userId, int $anio)
    {
        return $query
            ->where('user_id', $userId)
            ->whereYear('fecha', $anio)
            ->selectRaw('MONTH(fecha) as mes, CAST(COALESCE(SUM(monto), 0) AS CHAR) as total')
            ->groupByRaw('MONTH(fecha)')
            ->pluck('total', 'mes');
    }
}
