<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\TurnoController;
use App\Http\Controllers\Api\TurnoPantallaController;
use App\Filament\Resources\ConsultaExternaResource;
use Illuminate\Support\Facades\Log;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/pacientes/{numero_documento}', [PacienteController::class, 'show']);
Route::post('/turno', [TurnoController::class, 'store']);
Route::get('/turno-ultimo', [TurnoPantallaController::class, 'ultimo']);

Route::post('/pacientes', [PacienteController:: class, 'crear_paciente']);