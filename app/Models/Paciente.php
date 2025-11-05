<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{

    protected $table = 'paciente';
    protected $primaryKey = 'id_paciente';


    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'condicion_especial',
        'nombre',
        'apellido',
    ];

}
