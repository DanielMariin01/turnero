<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PortalSsoController;



Route::get('/', function () {
    return view('welcome');
});
//api para consultar por numero de documento del paciente http://127.0.0.1:8000/pacientes/${paciente.numero_documento}

Route::get('/pantalla', function () {
    return view('app'); // aquí se monta React
});

Route::get('/pantallaUrgencias', function () {
    return view('pantallaUrgencias');
});

Route::get('/pantallaQuimioterapia', function () {
    return view('pantallaQuimioterapia');
});


// Ingreso único desde el Portal Corporativo (el pase se valida dentro del controlador).
Route::get('/sso', PortalSsoController::class)->middleware('throttle:20,1')->name('sso');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
