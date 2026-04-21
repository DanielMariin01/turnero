<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\ActualizarTurnosCommand;
use App\Jobs\VerificarAlertasUrgencias;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::command('turnos:actualizar')
    ->everyFiveMinutes()
    ->withoutOverlapping();


Schedule::job(new VerificarAlertasUrgencias)->everyThreeMinutes();
