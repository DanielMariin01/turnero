<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{

    protected $table = 'turno';
    protected $primaryKey = 'id_turno';

    protected $fillable = [

       'fk_paciente',
       'numero_turno',
       'motivo',
       'hora',
       'fecha',
       'condicion',
       'estado'


    ];


    public function scopeHoy($query)
{
    return $query->whereDate('fecha', now());
}


public function paciente()
{
    return $this->belongsTo(Paciente::class, 'fk_paciente', 'id_paciente');
}




}
