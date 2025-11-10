<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});
//api para consultar por numero de documento del paciente http://127.0.0.1:8000/pacientes/${paciente.numero_documento}

Route::get('/debug-time', function () {
    return [
        'php_date_timezone' => ini_get('date.timezone'),
        'php_date' => date('Y-m-d H:i:s'),
        'carbon_now' => \Carbon\Carbon::now()->toDateTimeString(),
        'laravel_config_timezone' => config('app.timezone'),
    ];
});


