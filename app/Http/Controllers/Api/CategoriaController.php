<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FiltroCategoriasRequest;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(FiltroCategoriasRequest $request): JsonResponse
    {
        $query = Categoria::query()
            ->with('subcategorias')
            ->where(function ($query) use ($request) {
                $query
                    ->whereNull('user_id')
                    ->orWhere('user_id', $request->user()->id);
            });

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->query('tipo'));
        }

        return response()->json(
            $query
                ->orderBy('tipo')
                ->orderBy('nombre')
                ->get()
        );
    }

    public function store(StoreCategoriaRequest $request): JsonResponse
    {
        $categoria = Categoria::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json(
            $categoria->load('subcategorias'),
            201
        );
    }

    public function show(Request $request, Categoria $categoria): JsonResponse
    {
        if (! $this->visibleParaUsuario($categoria, $request->user()->id)) {
            abort(404);
        }

        return response()->json(
            $categoria->load('subcategorias')
        );
    }

    public function update(UpdateCategoriaRequest $request, Categoria $categoria): JsonResponse
    {
        if ($categoria->user_id !== $request->user()->id) {
            abort(404);
        }

        $categoria->update($request->validated());

        return response()->json(
            $categoria->load('subcategorias')
        );
    }

    public function destroy(Request $request, Categoria $categoria): JsonResponse
    {
        if ($categoria->user_id !== $request->user()->id) {
            abort(404);
        }

        $categoria->delete();

        return response()->json(null, 204);
    }

    private function visibleParaUsuario(Categoria $categoria, int $userId): bool
    {
        return $categoria->user_id === null || $categoria->user_id === $userId;
    }
}
