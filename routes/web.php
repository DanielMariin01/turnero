<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});
//api para consultar por numero de documento del paciente http://127.0.0.1:8000/pacientes/${paciente.numero_documento}



