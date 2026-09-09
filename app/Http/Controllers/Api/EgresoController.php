<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEgresoRequest;
use App\Http\Requests\UpdateEgresoRequest;
use App\Models\Egreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EgresoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Egreso::query()
            ->with(['categoria', 'subcategoria'])
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

    public function store(StoreEgresoRequest $request): JsonResponse
    {
        $egreso = Egreso::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json(
            $egreso->load(['categoria', 'subcategoria']),
            201
        );
    }

    public function show(Request $request, Egreso $egreso): JsonResponse
    {
        if ($egreso->user_id !== $request->user()->id) {
            abort(404);
        }

        return response()->json(
            $egreso->load(['categoria', 'subcategoria'])
        );
    }

    public function update(UpdateEgresoRequest $request, Egreso $egreso): JsonResponse
    {
        if ($egreso->user_id !== $request->user()->id) {
            abort(404);
        }

        $egreso->update($request->validated());

        return response()->json(
            $egreso->load(['categoria', 'subcategoria'])
        );
    }

    public function destroy(Request $request, Egreso $egreso): JsonResponse
    {
        if ($egreso->user_id !== $request->user()->id) {
            abort(404);
        }

        $egreso->delete();

        return response()->json(null, 204);
    }
}
