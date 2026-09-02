<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('reportes.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_reportes(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reportes.index'));
        $response->assertOk();
    }

    public function test_generar_un_reporte_lo_deja_listado_en_el_modulo(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $mantenimiento = $this->mantenimientoEjecutado();

        $this->assertDatabaseCount('reportes', 0);

        $this->get(route('mantenimientos.reporte', $mantenimiento))->assertOk();

        $this->assertDatabaseHas('reportes', [
            'mantenimiento_id' => $mantenimiento->id,
            'empresa_id' => $mantenimiento->empresa_id,
            'tipo' => 'preventivo',
            'generado_por' => $usuario->id,
            'ultimo_generado_por' => $usuario->id,
            'veces_generado' => 1,
        ]);

        Livewire::test('pages::reportes.index')
            ->assertSee('RP-00001')
            ->assertSee('MONITOR DE SIGNOS VITALES')
            ->assertSee('Clínica del Norte');
    }

    public function test_volver_a_generar_el_reporte_no_duplica_la_fila(): void
    {
        $primero = User::factory()->create();
        $segundo = User::factory()->create();

        $mantenimiento = $this->mantenimientoEjecutado();

        $this->actingAs($primero)->get(route('mantenimientos.reporte', $mantenimiento))->assertOk();
        $this->actingAs($segundo)->get(route('mantenimientos.reporte', $mantenimiento))->assertOk();

        $this->assertDatabaseCount('reportes', 1);

        $reporte = Reporte::firstOrFail();

        $this->assertSame(2, $reporte->veces_generado);
        // La primera emisión conserva su autor; la última registra al segundo.
        $this->assertSame($primero->id, $reporte->generado_por);
        $this->assertSame($segundo->id, $reporte->ultimo_generado_por);
    }

    public function test_una_orden_sin_ejecutar_no_genera_reporte(): void
    {
        $this->actingAs(User::factory()->create());

        $mantenimiento = $this->mantenimientoEjecutado();
        $mantenimiento->update(['estado' => 'programado', 'fecha_ejecucion' => null]);

        $this->get(route('mantenimientos.reporte', $mantenimiento))->assertNotFound();

        $this->assertDatabaseCount('reportes', 0);
    }

    public function test_el_listado_se_filtra_por_tipo(): void
    {
        $this->actingAs(User::factory()->create());

        $preventivo = $this->mantenimientoEjecutado();
        $correctivo = $this->mantenimientoEjecutado();
        $correctivo->update(['tipo' => 'correctivo', 'motivo' => 'Pantalla intermitente.']);

        $this->get(route('mantenimientos.reporte', $preventivo));
        $this->get(route('mantenimientos.reporte', $correctivo));

        Livewire::test('pages::reportes.index')
            ->assertSee('RP-00001')
            ->assertSee('RP-00002')
            ->call('filtrarPorTipo', 'correctivo')
            ->assertSet('filtroTipo', 'correctivo')
            ->assertSee('RP-00002')
            ->assertDontSee('RP-00001');
    }

    public function test_un_reporte_puede_retirarse_del_listado(): void
    {
        $this->actingAs(User::factory()->create());

        $mantenimiento = $this->mantenimientoEjecutado();
        $this->get(route('mantenimientos.reporte', $mantenimiento));

        $reporte = Reporte::firstOrFail();

        Livewire::test('pages::reportes.index')
            ->call('confirmarEliminacion', $reporte->id)
            ->assertSet('reporteAEliminar', $reporte->id)
            ->call('eliminar')
            ->assertSet('reporteAEliminar', null);

        $this->assertDatabaseCount('reportes', 0);
        // Retirar el reporte no toca la orden que documenta.
        $this->assertDatabaseHas('mantenimientos', ['id' => $mantenimiento->id, 'deleted_at' => null]);
    }

    public function test_el_modulo_avisa_de_las_ordenes_ejecutadas_sin_reporte(): void
    {
        $this->actingAs(User::factory()->create());

        $this->mantenimientoEjecutado();

        Livewire::test('pages::reportes.index')
            ->assertSet('reporteAEliminar', null)
            ->assertSee('sin reporte generado');
    }

    /**
     * Orden cerrada sobre un equipo con empresa, lista para reportarse.
     */
    private function mantenimientoEjecutado(): Mantenimiento
    {
        $empresa = Empresa::firstOrCreate(
            ['nombre' => 'Clínica del Norte'],
            ['nit' => '900111222-1', 'ciudad' => 'Riohacha'],
        );

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
