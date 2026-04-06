<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\TurnoController;
use App\Http\Controllers\Api\TurnoPantallaController;
use App\Filament\Resources\ConsultaExternaResource;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\QZTrayController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/pacientes/{numero_documento}', [PacienteController::class, 'show']);
Route::post('/turno', [TurnoController::class, 'store']);
//api para consultar el ultimo turno que fue llamado
Route::get('/turno-ultimo', [TurnoPantallaController::class, 'ultimo']);
//api para crear un nuevo paciente
Route::post('/pacientes', [PacienteController::class, 'crear_paciente']);
Route::get('/turnos-llamados', [TurnoPantallaController::class, 'turnosLlamados']);
//Route::get('/turnos-llamadosmedicos', [TurnoPantallaController:: class, 'turnosLlamadosMedicos']);
Route::get('/turnos-medicos', [TurnoPantallaController::class, 'turnosMedico']);

//api para crear la ruta para imprimit turno en urgencias
Route::get('/turnos/{id_turno}/imprimir', [TurnoController::class, 'imprimir']);
//api para consultar el ultimo turno que se llamo en urgencias
Route::get('/turnoUltimoUrgencias', [TurnoPantallaController::class, 'turnoUltimoUrgencias']);
//api para consultar el ultimo turno que se llamo en urgencias 
Route::get('/turnoMedicoUrgencias', [TurnoPantallaController::class, 'turnoMedicoUrgencias']);
//api para consultar una lista de los turnos que han sido llamaodis en el area de urgencias
Route::get('/turnosLlamadosUrgencias', [TurnoPantallaController::class, 'turnosLlamadosUrgencias']);

//API DE LLAMADO DE TURNOS AREA QUIMIOTERAPIA
Route::get('/turnoUltimoQuimioterapia', [TurnoPantallaController::class, 'turnoUltimoQuimioterapia']);
//API PARA CONSULTAR LOS ULTIMOS TURNOS QUE SE HAN LLAMADO EN QUIMIOTERAPIA
Route::get('turnosLlamadosQuimioterapia', [TurnoPantallaController::class, 'turnosLlamadosQuimioterapia']);

//API PARA EL LLAMADO DE CERTIFICADOS DE LA IMPRESORA Y EL PROGRAMA QZ TRAY
Route::get('/qz/certificate', [QZTrayController::class, 'getCertificate']);
Route::post('/qz/sign', [QZTrayController::class, 'signMessage']);

Route::get('/qz/test', [QZTrayController::class, 'test']);
