<?php

namespace Tests\Unit;

use App\Support\AreaDeTrabajo;
use PHPUnit\Framework\TestCase;

/** Prueba pura de las reglas de áreas: no usa la base de datos ni arranca Laravel. */
class AreaDeTrabajoTest extends TestCase
{
    public function test_conoce_las_cuatro_areas_del_portal(): void
    {
        foreach (['urgencias', 'consulta_externa', 'quimioterapia', 'admisiones_oncologia'] as $area) {
            $this->assertTrue(AreaDeTrabajo::existe($area));
        }

        $this->assertFalse(AreaDeTrabajo::existe('inventada'));
        $this->assertFalse(AreaDeTrabajo::existe(null));
    }

    public function test_prepagada_pertenece_a_urgencias(): void
    {
        $this->assertContains('medico_consulta_prepagada', AreaDeTrabajo::rolesDe('urgencias'));
        $this->assertNotContains('medico_consulta_prepagada', AreaDeTrabajo::rolesDe('consulta_externa'));
    }

    public function test_un_rol_habilita_solo_su_area(): void
    {
        $this->assertTrue(AreaDeTrabajo::habilita(['medico_urgencias'], 'urgencias'));
        $this->assertFalse(AreaDeTrabajo::habilita(['medico_urgencias'], 'consulta_externa'));
        $this->assertFalse(AreaDeTrabajo::habilita([], 'urgencias'));
    }

    public function test_quien_tiene_dos_roles_puede_elegir_cualquiera_de_sus_dos_areas(): void
    {
        $roles = ['medico', 'medico_urgencias'];

        $this->assertTrue(AreaDeTrabajo::habilita($roles, 'consulta_externa'));
        $this->assertTrue(AreaDeTrabajo::habilita($roles, 'urgencias'));
        $this->assertFalse(AreaDeTrabajo::habilita($roles, 'quimioterapia'));
    }

    public function test_el_administrador_entra_a_cualquier_area(): void
    {
        $this->assertTrue(AreaDeTrabajo::habilita(['admin'], 'quimioterapia'));
    }

    public function test_con_un_area_activa_solo_cuentan_los_roles_de_esa_area(): void
    {
        $pedidos = ['admin', 'admisiones_consultaExterna', 'medico_urgencias'];

        $this->assertSame(['admin', 'medico_urgencias'], AreaDeTrabajo::limitarRoles($pedidos, 'urgencias'));
        $this->assertSame(['admin', 'admisiones_consultaExterna'], AreaDeTrabajo::limitarRoles($pedidos, 'consulta_externa'));
    }

    public function test_sin_area_activa_no_se_limita_nada(): void
    {
        $pedidos = ['admisiones_consultaExterna', 'medico_urgencias'];

        $this->assertSame($pedidos, AreaDeTrabajo::limitarRoles($pedidos, null));
    }

    public function test_cada_area_tiene_pantallas_de_inicio(): void
    {
        foreach (['urgencias', 'consulta_externa', 'quimioterapia', 'admisiones_oncologia'] as $area) {
            $this->assertNotEmpty(AreaDeTrabajo::pantallasDe($area));
        }
    }
}
