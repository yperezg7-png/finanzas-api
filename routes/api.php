<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EgresoController;
use App\Http\Controllers\Api\IngresoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard/resumen', [DashboardController::class, 'resumen']);
    Route::get('/dashboard/egresos-por-categoria', [DashboardController::class, 'egresosPorCategoria']);
    Route::get('/dashboard/resumen-anual', [DashboardController::class, 'resumenAnual']);
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('egresos', EgresoController::class);
    Route::apiResource('ingresos', IngresoController::class);
});
