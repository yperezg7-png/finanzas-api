<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FiltrosMovimientosRequest;
use App\Http\Requests\StoreIngresoRequest;
use App\Http\Requests\UpdateIngresoRequest;
use App\Models\Ingreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngresoController extends Controller
{
    public function index(FiltrosMovimientosRequest $request): JsonResponse
    {
        $query = Ingreso::query()
            ->with('categoria')
            ->where('user_id', $request->user()->id);

        if ($request->filled('anio')) {
            $query->whereYear('fecha', (int) $request->query('anio'));
        }

        if ($request->filled('mes')) {
            $query->whereMonth('fecha', (int) $request->query('mes'));
        }

        return response()->json(
            $query->orderByDesc('fecha')->get()
        );
    }

    public function store(StoreIngresoRequest $request): JsonResponse
    {
        $ingreso = Ingreso::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json(
            $ingreso->load('categoria'),
            201
        );
    }

    public function show(Request $request, Ingreso $ingreso): JsonResponse
    {
        if ($ingreso->user_id !== $request->user()->id) {
            abort(404);
        }

        return response()->json(
            $ingreso->load('categoria')
        );
    }

    public function update(UpdateIngresoRequest $request, Ingreso $ingreso): JsonResponse
    {
        if ($ingreso->user_id !== $request->user()->id) {
            abort(404);
        }

        $ingreso->update($request->validated());

        return response()->json(
            $ingreso->load('categoria')
        );
    }

    public function destroy(Request $request, Ingreso $ingreso): JsonResponse
    {
        if ($ingreso->user_id !== $request->user()->id) {
            abort(404);
        }

        $ingreso->delete();

        return response()->json(null, 204);
    }
}
