<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\AreaDeTrabajo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasRoles {
        hasRole as protected hasRoleSinLimiteDeArea;
    }

    /**
     * Si se ingresó desde el portal eligiendo un área, solo cuentan los roles de esa área
     * (el administrador siempre cuenta). Sin área elegida el comportamiento no cambia.
     * Todas las pantallas usan hasAnyRole(), que llega aquí a través de hasRole().
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        $area = AreaDeTrabajo::activa();

        if ($area === null || ! (is_string($roles) || is_array($roles))) {
            return $this->hasRoleSinLimiteDeArea($roles, $guard);
        }

        $nombres = is_string($roles) ? explode('|', $roles) : Arr::flatten($roles);
        $validos = AreaDeTrabajo::limitarRoles($nombres, $area);

        // Un nombre suelto se consulta tal cual (evita que Spatie vuelva a entrar aquí sin fin).
        if (is_string($roles) && ! str_contains($roles, '|')) {
            return $validos !== [] && $this->hasRoleSinLimiteDeArea($roles, $guard);
        }

        return $this->hasRoleSinLimiteDeArea($validos, $guard);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'activo',
        'cedula'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    
    public function consultorios()
    {
        return $this->belongsToMany(Consultorio::class, 'usuario_consultorio')
                    ->withPivot( 'fecha');
    }
}
