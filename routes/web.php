<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PacienteController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/pacientes/{numero_documento}', [PacienteController::class, 'show']);
Route::post('/pacientes',[PacienteController::class,'store']);

