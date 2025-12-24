<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AgencySyncController;
use App\Http\Controllers\AgenciaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\IncidenteController;

// Asegúrate de que el middleware 'sso' esté registrado en bootstrap/app.php
Route::middleware('sso')->group(function () {
    // Agencias
    Route::post('/sincronizar-agencias', [AgencySyncController::class, 'sync']);
    Route::get('/agencias', [AgenciaController::class, 'index']);

    // Módulos de Inventario
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('inventarios', InventarioController::class);
    Route::apiResource('incidentes', IncidenteController::class);

    // Ruta personalizada para historial de incidentes
    Route::get('/inventarios/{id}/incidentes', [IncidenteController::class, 'byInventario']);
});
