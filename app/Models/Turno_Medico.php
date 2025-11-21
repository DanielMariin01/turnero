<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno_Medico extends Model
{
        
    protected $table = 'turno_medico';
    protected $primaryKey = 'id_turno_medico';


    protected $fillable = [
        'fk_paciente',
        'fk_users',
        'hora'
     
    ];

    public function medico()
    {
        return $this->belongsTo(User::class, 'fk_users'); // clave foránea al usuario
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'fk_paciente');
    }
}
