<?php

namespace App\Support;

use App\Filament\Resources\ConsultaExternaResource;
use App\Filament\Resources\ConsultaMedicaPrepagadaResource;
use App\Filament\Resources\ConsultaMedicaUrgenciasResource;
use App\Filament\Resources\ConsultorioUrgenciasResource;
use App\Filament\Resources\MedicoResource;
use App\Filament\Resources\OncologiaResource;
use App\Filament\Resources\PedirCitaResource;
use App\Filament\Resources\UrgenciasResource;

/**
 * Áreas de trabajo que se eligen en el Portal Corporativo ("¿dónde vas a trabajar hoy?").
 * Cada área define qué roles la habilitan y cuáles son sus pantallas, en orden de preferencia
 * para decidir a dónde llega el usuario. Los ids deben coincidir con los del portal (config/sso.php).
 */
final class AreaDeTrabajo
{
    /** Clave de sesión donde queda el área elegida al entrar. */
    public const SESION = 'area_trabajo';

    /** Este rol entra a cualquier área y no se limita por ella. */
    public const ROL_ADMINISTRADOR = 'admin';

    private const AREAS = [
        'urgencias' => [
            'roles' => ['admisiones_urgencias', 'medico_urgencias', 'medico_consulta_urgencias', 'medico_consulta_prepagada'],
            'pantallas' => [
                UrgenciasResource::class,
                ConsultorioUrgenciasResource::class,
                ConsultaMedicaUrgenciasResource::class,
                ConsultaMedicaPrepagadaResource::class,
            ],
        ],
        'consulta_externa' => [
            'roles' => ['admisiones_consultaExterna', 'medico'],
            'pantallas' => [ConsultaExternaResource::class, MedicoResource::class],
        ],
        'quimioterapia' => [
            'roles' => ['Quimioterapia'],
            'pantallas' => [OncologiaResource::class],
        ],
        'admisiones_oncologia' => [
            'roles' => ['admisiones_oncologia'],
            'pantallas' => [PedirCitaResource::class],
        ],
    ];

    public static function existe(?string $area): bool
    {
        return $area !== null && isset(self::AREAS[$area]);
    }

    /** @return list<string> */
    public static function rolesDe(string $area): array
    {
        return self::AREAS[$area]['roles'] ?? [];
    }

    /** @return list<class-string> Pantallas del área, de la más a la menos preferida como inicio. */
    public static function pantallasDe(string $area): array
    {
        return self::AREAS[$area]['pantallas'] ?? [];
    }

    /** ¿Alguno de los roles del usuario lo habilita para trabajar en esta área? */
    public static function habilita(array $rolesDelUsuario, string $area): bool
    {
        return in_array(self::ROL_ADMINISTRADOR, $rolesDelUsuario, true)
            || array_intersect($rolesDelUsuario, self::rolesDe($area)) !== [];
    }

    /**
     * Deja, de los roles que una pantalla pide, solo los que valen para el área activa.
     * Sin área activa (ingreso normal con correo y contraseña) no se limita nada.
     *
     * @param  list<string>  $rolesSolicitados
     * @return list<string>
     */
    public static function limitarRoles(array $rolesSolicitados, ?string $areaActiva): array
    {
        if (! self::existe($areaActiva)) {
            return $rolesSolicitados;
        }

        $validos = [...self::rolesDe($areaActiva), self::ROL_ADMINISTRADOR];

        return array_values(array_intersect($rolesSolicitados, $validos));
    }

    /** Área elegida en la sesión actual, o null si se ingresó de forma normal. */
    public static function activa(): ?string
    {
        if (! app()->bound('request') || ! request()->hasSession()) {
            return null;
        }

        $area = request()->session()->get(self::SESION);

        return self::existe($area) ? $area : null;
    }
}
