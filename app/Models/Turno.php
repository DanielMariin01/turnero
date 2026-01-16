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
       'estado',
       'fk_consultorio',
       'fk_modulo',
       'observaciones',
       'paciente_urgencias',
       'llamado_en',
       'hora_llamado',
       'hora_atendido',
       'hora_llamado_medico',
       'hora_llamado_facturar',
       'hora_finalizacion'



    ];


    public function scopeHoy($query)
{
    return $query->whereDate('fecha', now());
}


public function paciente()
{
    return $this->belongsTo(Paciente::class, 'fk_paciente', 'id_paciente');
}

public function consultorio()
{
    return $this->belongsTo(Consultorio::class, 'fk_consultorio', 'id_consultorio');
}

public function modulo()
{
    return $this->belongsTo(Modulo::class, 'fk_modulo', 'id_modulo');
}


public function getPrioridadTextoAttribute()
{
    return match($this->condicion) {
        'movilidad_reducida', 'adulto_mayor', 'gestante' => 'Alta',
        'acompañado_con_un_menor' => 'Media',
        default => 'Baja',
    };
}

}
