<?php

use Illuminate\Support\Facades\Route;



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


Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
