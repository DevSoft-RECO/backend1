<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AgencySyncController;

// Asegúrate de que el middleware 'sso' esté registrado en bootstrap/app.php
Route::middleware('sso')->group(function () {
    Route::post('/sincronizar-agencias', [AgencySyncController::class, 'sync']);
});
