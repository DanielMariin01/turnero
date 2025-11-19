<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
     
    protected $table = 'modulo';
    protected $primaryKey = 'id_modulo';


    protected $fillable = [
        'nombre',
        'descripcion',
     
    ];
}
