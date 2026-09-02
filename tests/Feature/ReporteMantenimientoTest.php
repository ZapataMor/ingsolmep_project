<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteMantenimientoTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $mantenimiento = $this->mantenimientoEjecutado();

        $response = $this->get(route('mantenimientos.reporte', $mantenimiento));
        $response->assertRedirect(route('login'));
    }

    public function test_una_orden_ejecutada_genera_su_reporte(): void
    {
        $this->actingAs(User::factory()->create());

        $mantenimiento = $this->mantenimientoEjecutado();

        $response = $this->get(route('mantenimientos.reporte', $mantenimiento));

        $response->assertOk();
        $response->assertSee($mantenimiento->codigo());
        $response->assertSee('Reporte de mantenimiento preventivo');
        $response->assertSee('MONITOR DE SIGNOS VITALES');
        $response->assertSee('Clínica del Norte');
    }

    public function test_una_orden_todavia_abierta_no_tiene_reporte(): void
    {
        $this->actingAs(User::factory()->create());

        $mantenimiento = $this->mantenimientoEjecutado();
        $mantenimiento->update(['estado' => 'programado', 'fecha_ejecucion' => null]);

        $response = $this->get(route('mantenimientos.reporte', $mantenimiento));
        $response->assertNotFound();
    }

    public function test_el_reporte_preventivo_lista_la_rutina_de_subtareas(): void
    {
        $this->actingAs(User::factory()->create());

        $mantenimiento = $this->mantenimientoEjecutado();
        $mantenimiento->update([
            'subtareas' => ['prueba_funcionamiento' => true, 'limpieza_filtros' => false],
            'accesorios_estado' => ['bateria' => 'R'],
        ]);

        $response = $this->get(route('mantenimientos.reporte', $mantenimiento));

        $response->assertOk();
        $response->assertSee('Prueba de funcionamiento');
        $response->assertSee('Limpieza y revisión de filtros');
        $response->assertSee('1 de 2 ejecutadas');
        $response->assertSee('Batería');
    }

    public function test_el_reporte_correctivo_encabeza_la_falla_reportada(): void
    {
        $this->actingAs(User::factory()->create());

        $mantenimiento = $this->mantenimientoEjecutado();
        $mantenimiento->update([
            'tipo' => 'correctivo',
            'motivo' => 'La pantalla se apaga de forma intermitente.',
            'subtareas' => null,
        ]);

        $response = $this->get(route('mantenimientos.reporte', $mantenimiento));

        $response->assertOk();
        $response->assertSee('Reporte de mantenimiento correctivo');
        $response->assertSee('Falla reportada');
        $response->assertSee('La pantalla se apaga de forma intermitente.');
        // La rutina de subtareas es propia del preventivo.
        $response->assertDontSee('Rutina de subtareas');
    }

    /**
     * Orden cerrada sobre un equipo con empresa, que es lo que el reporte pide.
     */
    private function mantenimientoEjecutado(): Mantenimiento
    {
        $empresa = Empresa::create([
            'nombre' => 'Clínica del Norte',
            'nit' => '900111222-1',
            'ciudad' => 'Riohacha',
        ]);

        $equipo = Equipo::create([
            'empresa_id' => $empresa->id,
            'descripcion' => 'MONITOR DE SIGNOS VITALES',
            'numero_serie' => 'SN-0001',
        ]);

        return Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'empresa_id' => $empresa->id,
            'tipo' => 'preventivo',
            'estado' => 'ejecutado',
            'fecha_programada' => '2026-08-10',
            'fecha_ejecucion' => '2026-08-12',
            'tecnico' => 'Luis Zapata',
            'descripcion' => 'Rutina trimestral ejecutada sin novedad.',
        ]);
    }
}
