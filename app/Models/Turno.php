<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{

    protected $table = 'turno';
    protected $primary_key = 'id_turno';

    protected $fillable = [

       'fk_paciente',
       'numero_turno',
       'hora',
       'fecha',
       'condicion',
       'estado'


    ];

}
