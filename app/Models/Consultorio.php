<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultorio extends Model
{
    protected $table = 'consultorio';
    protected $primaryKey = 'id_consultorio';


    protected $fillable = [
        'nombre',
        'ubicacion',
     
    ];


   // public function usuarios()
   // {
      //  return $this->belongsToMany(User::class, 'user_consultorio')
                   // ->withPivot('fecha');
   // }

    public function turnos()
{
    return $this->hasMany(Turno::class, 'fk_consultorio', 'id_consultorio');
}



}
