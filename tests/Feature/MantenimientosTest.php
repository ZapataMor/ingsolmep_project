<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Tests\TestCase;

class MantenimientosTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('mantenimientos.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_mantenimientos(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('mantenimientos.index'));
        $response->assertOk();
    }

    public function test_un_mantenimiento_preventivo_puede_asignarse_a_un_equipo(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipo();

        Livewire::test('pages::mantenimientos.index')
            ->call('abrirCreacion', 'preventivo')
            ->assertSet('tipo', 'preventivo')
            ->set('equipo_id', (string) $equipo->id)
            ->set('fecha_programada', '2026-10-15')
            ->set('tecnico', 'Luis Zapata')
            ->set('descripcion', 'Rutina trimestral.')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSet('mostrarFormulario', false);

        $this->assertDatabaseHas('mantenimientos', [
            'equipo_id' => $equipo->id,
            'empresa_id' => $equipo->empresa_id,
            'tipo' => 'preventivo',
            'estado' => 'programado',
            'tecnico' => 'Luis Zapata',
        ]);
    }

    public function test_un_mantenimiento_correctivo_exige_la_falla_reportada(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipo();

        Livewire::test('pages::mantenimientos.index')
            ->call('abrirCreacion', 'correctivo')
            ->set('equipo_id', (string) $equipo->id)
            ->set('fecha_programada', '2026-10-15')
            ->call('guardar')
            ->assertHasErrors(['motivo']);

        $this->assertDatabaseCount('mantenimientos', 0);
    }

    public function test_el_formulario_exige_equipo_y_fecha_programada(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::mantenimientos.index')
            ->call('abrirCreacion')
            ->set('fecha_programada', '')
            ->call('guardar')
            ->assertHasErrors(['equipo_id', 'fecha_programada']);

        $this->assertDatabaseCount('mantenimientos', 0);
    }

    public function test_cerrar_una_orden_exige_la_fecha_de_ejecucion(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipo();

        Livewire::test('pages::mantenimientos.index')
            ->call('abrirCreacion')
            ->set('equipo_id', (string) $equipo->id)
            ->set('fecha_programada', '2026-10-15')
            ->set('estado', 'ejecutado')
            ->set('fecha_ejecucion', '')
            ->call('guardar')
            ->assertHasErrors(['fecha_ejecucion']);
    }

    public function test_una_orden_ejecutada_actualiza_el_ultimo_mantenimiento_del_equipo(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipo();

        $mantenimiento = Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'empresa_id' => $equipo->empresa_id,
            'tipo' => 'preventivo',
            'estado' => 'programado',
            'fecha_programada' => '2026-08-20',
        ]);

        Livewire::test('pages::mantenimientos.index')->call('marcarEjecutado', $mantenimiento->id);

        $mantenimiento->refresh();

        $this->assertSame('ejecutado', $mantenimiento->estado);
        $this->assertSame(
            Date::today()->format('Y-m-d'),
            $equipo->fresh()->ultimo_mantenimiento?->format('Y-m-d'),
        );
    }

    public function test_el_listado_muestra_los_dos_tipos_y_puede_filtrarse_por_tipo(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipo();

        Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'tipo' => 'preventivo',
            'estado' => 'programado',
            'fecha_programada' => '2026-09-10',
        ]);

        Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'tipo' => 'correctivo',
            'estado' => 'programado',
            'fecha_programada' => '2026-09-12',
            'motivo' => 'El equipo no enciende.',
        ]);

        Livewire::test('pages::mantenimientos.index')
            ->assertSee('MP-')
            ->assertSee('MC-')
            ->set('filtroTipo', 'correctivo')
            ->assertSee('MC-')
            ->assertDontSee('MP-');
    }

    public function test_el_resumen_cuenta_preventivos_correctivos_y_vencidos(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipo();

        Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'tipo' => 'preventivo',
            'estado' => 'programado',
            'fecha_programada' => Date::today()->subDays(3),
        ]);

        Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'tipo' => 'correctivo',
            'estado' => 'ejecutado',
            'fecha_programada' => Date::today(),
            'fecha_ejecucion' => Date::today(),
            'motivo' => 'Cambio de fusible.',
        ]);

        $resumen = Livewire::test('pages::mantenimientos.index')->instance()->resumen();

        $this->assertSame(2, $resumen['total']);
        $this->assertSame(1, $resumen['preventivos']);
        $this->assertSame(1, $resumen['correctivos']);
        $this->assertSame(1, $resumen['pendientes']);
        $this->assertSame(1, $resumen['vencidos']);
        $this->assertSame(1, $resumen['ejecutados']);
    }

    public function test_la_ficha_de_la_orden_muestra_el_equipo_y_la_falla(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipo();

        $mantenimiento = Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'empresa_id' => $equipo->empresa_id,
            'tipo' => 'correctivo',
            'estado' => 'programado',
            'fecha_programada' => '2026-09-10',
            'motivo' => 'El equipo no enciende.',
            'tecnico' => 'Luis Zapata',
        ]);

        Livewire::test('pages::mantenimientos.index')
            ->call('verMantenimiento', $mantenimiento->id)
            ->assertSet('mantenimientoVisto', $mantenimiento->id)
            ->assertSee('MONITOR DE SIGNOS VITALES')
            ->assertSee('El equipo no enciende.')
            ->assertSee('Falla reportada');
    }

    public function test_las_tarjetas_de_indicadores_abren_su_listado(): void
    {
        $this->actingAs(User::factory()->create());

        Mantenimiento::create([
            'equipo_id' => $this->equipo()->id,
            'tipo' => 'preventivo',
            'estado' => 'programado',
            'fecha_programada' => Date::today()->subDays(5),
        ]);

        Livewire::test('pages::mantenimientos.index')
            ->call('verListado', 'vencidos')
            ->assertSet('listadoVisto', 'vencidos')
            ->assertSee('Mantenimientos vencidos')
            ->assertSee('MONITOR DE SIGNOS VITALES');
    }

    public function test_un_mantenimiento_puede_editarse(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipo();

        $mantenimiento = Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'tipo' => 'preventivo',
            'estado' => 'programado',
            'fecha_programada' => '2026-09-10',
        ]);

        Livewire::test('pages::mantenimientos.index')
            ->call('editar', $mantenimiento->id)
            ->assertSet('equipo_id', (string) $equipo->id)
            ->assertSet('fecha_programada', '2026-09-10')
            ->set('tecnico', 'Ana Pérez')
            ->set('estado', 'en_proceso')
            ->call('guardar')
            ->assertHasNoErrors();

        $mantenimiento->refresh();

        $this->assertSame('Ana Pérez', $mantenimiento->tecnico);
        $this->assertSame('en_proceso', $mantenimiento->estado);
    }

    public function test_un_mantenimiento_se_elimina_de_forma_logica(): void
    {
        $this->actingAs(User::factory()->create());

        $mantenimiento = Mantenimiento::create([
            'equipo_id' => $this->equipo()->id,
            'tipo' => 'correctivo',
            'estado' => 'programado',
            'fecha_programada' => '2026-09-10',
            'motivo' => 'Pantalla intermitente.',
        ]);

        Livewire::test('pages::mantenimientos.index')
            ->call('confirmarEliminacion', $mantenimiento->id)
            ->assertSet('mantenimientoAEliminar', $mantenimiento->id)
            ->call('eliminar')
            ->assertSet('mantenimientoAEliminar', null);

        $this->assertSoftDeleted($mantenimiento);
    }

    public function test_el_formulario_hereda_la_rutina_de_subtareas_del_equipo(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipo();
        $equipo->update(['subtareas' => ['prueba_funcionamiento' => true, 'prueba_fugas' => false]]);

        Livewire::test('pages::mantenimientos.index')
            ->call('abrirCreacion')
            ->set('equipo_id', (string) $equipo->id)
            ->assertSet('subtareas.prueba_funcionamiento', true)
            ->assertSet('subtareas.prueba_fugas', false);
    }

    /**
     * Equipo mínimo con empresa, que es lo que necesita una orden.
     */
    private function equipo(): Equipo
    {
        $empresa = Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '900111222-1']);

        return Equipo::create([
            'empresa_id' => $empresa->id,
            'descripcion' => 'MONITOR DE SIGNOS VITALES',
            'numero_serie' => 'SN-0001',
        ]);
    }
}
