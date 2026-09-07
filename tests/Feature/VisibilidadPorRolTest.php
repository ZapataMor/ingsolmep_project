<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Lo que ve y lo que puede tocar cada perfil.
 *
 * El recorte no vive en las pantallas sino en un scope global, así que estas
 * pruebas van contra los modelos y contra las acciones de los componentes: es
 * ahí donde una fuga se notaría.
 */
class VisibilidadPorRolTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $propia;

    private Empresa $ajena;

    private Equipo $equipoPropio;

    private Equipo $equipoAjeno;

    private Mantenimiento $ordenPropia;

    private Mantenimiento $ordenAjena;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propia = Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '900111222-1']);
        $this->ajena = Empresa::create(['nombre' => 'Hospital del Sur', 'nit' => '900333444-2']);

        $this->equipoPropio = Equipo::create([
            'empresa_id' => $this->propia->id,
            'descripcion' => 'MONITOR DE SIGNOS VITALES',
            'numero_serie' => 'SN-0001',
        ]);

        $this->equipoAjeno = Equipo::create([
            'empresa_id' => $this->ajena->id,
            'descripcion' => 'VENTILADOR MECÁNICO',
            'numero_serie' => 'SN-0002',
        ]);

        $this->ordenPropia = Mantenimiento::create([
            'equipo_id' => $this->equipoPropio->id,
            'empresa_id' => $this->propia->id,
            'tipo' => 'preventivo',
            'estado' => 'programado',
            'fecha_programada' => '2026-09-10',
        ]);

        $this->ordenAjena = Mantenimiento::create([
            'equipo_id' => $this->equipoAjeno->id,
            'empresa_id' => $this->ajena->id,
            'tipo' => 'preventivo',
            'estado' => 'programado',
            'fecha_programada' => '2026-09-11',
        ]);
    }

    // ------------------------------------------------------------------
    // Institución
    // ------------------------------------------------------------------

    public function test_la_institucion_solo_ve_lo_suyo(): void
    {
        $this->actingAs($this->usuarioDeInstitucion());

        $this->assertSame([$this->propia->id], Empresa::pluck('id')->all());
        $this->assertSame([$this->equipoPropio->id], Equipo::pluck('id')->all());
        $this->assertSame([$this->ordenPropia->id], Mantenimiento::pluck('id')->all());
    }

    public function test_la_institucion_no_alcanza_una_orden_ajena_por_enlace_directo(): void
    {
        $this->ordenAjena->update(['estado' => 'ejecutado', 'fecha_ejecucion' => '2026-09-11']);

        $this->actingAs($this->usuarioDeInstitucion());

        $this->get(route('mantenimientos.reporte', $this->ordenAjena))->assertNotFound();
    }

    public function test_la_institucion_no_entra_al_modulo_de_empresas(): void
    {
        $this->actingAs($this->usuarioDeInstitucion());

        $this->get(route('empresas.index'))->assertForbidden();
    }

    public function test_el_menu_no_ofrece_pantallas_que_el_perfil_no_puede_abrir(): void
    {
        $this->actingAs($this->usuarioDeInstitucion());

        $this->get(route('panel'))
            ->assertOk()
            ->assertSee(route('mantenimientos.index'))
            ->assertDontSee(route('empresas.index'))
            ->assertDontSee(route('usuarios.index'));

        $this->actingAs(User::factory()->create());

        $this->get(route('panel'))
            ->assertOk()
            ->assertSee(route('empresas.index'))
            ->assertSee(route('usuarios.index'));
    }

    public function test_la_institucion_no_toca_el_inventario(): void
    {
        $this->actingAs($this->usuarioDeInstitucion());

        Livewire::test('pages::equipos.index')
            ->call('alternarActivo', $this->equipoPropio->id)
            ->assertForbidden();

        Livewire::test('pages::equipos.index')
            ->call('abrirCreacion')
            ->assertForbidden();
    }

    public function test_la_institucion_no_asigna_ni_cierra_ordenes(): void
    {
        $this->actingAs($this->usuarioDeInstitucion());

        Livewire::test('pages::mantenimientos.index')
            ->call('abrirCreacion')
            ->assertForbidden();

        Livewire::test('pages::mantenimientos.index')
            ->call('marcarEjecutado', $this->ordenPropia->id)
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Técnico
    // ------------------------------------------------------------------

    public function test_el_tecnico_solo_ve_las_ordenes_que_tiene_asignadas(): void
    {
        $tecnico = $this->usuarioTecnicoConOrden();

        $this->actingAs($tecnico);

        $this->assertSame([$this->ordenPropia->id], Mantenimiento::pluck('id')->all());
        $this->assertSame([$this->equipoPropio->id], Equipo::pluck('id')->all());
        $this->assertSame([$this->propia->id], Empresa::pluck('id')->all());
    }

    public function test_el_tecnico_cierra_su_orden(): void
    {
        $this->actingAs($this->usuarioTecnicoConOrden());

        Livewire::test('pages::mantenimientos.index')
            ->call('marcarEjecutado', $this->ordenPropia->id)
            ->assertOk();

        $this->assertSame('ejecutado', $this->ordenPropia->refresh()->estado);
    }

    public function test_el_tecnico_no_reasigna_ni_reprograma_su_orden(): void
    {
        $tecnico = $this->usuarioTecnicoConOrden();
        $otro = User::factory()->tecnico()->create();

        $this->actingAs($tecnico);

        Livewire::test('pages::mantenimientos.index')
            ->call('editar', $this->ordenPropia->id)
            ->set('fecha_programada', '2027-01-01')
            ->set('tecnico_id', (string) $otro->id)
            ->set('estado', 'en_proceso')
            ->set('observaciones', 'Se revisó el cableado.')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->ordenPropia->refresh();

        // Lo que documentó queda; lo que planeó otro, intacto.
        $this->assertSame('en_proceso', $this->ordenPropia->estado);
        $this->assertSame('Se revisó el cableado.', $this->ordenPropia->observaciones);
        $this->assertSame($tecnico->id, $this->ordenPropia->tecnico_id);
        $this->assertSame('2026-09-10', $this->ordenPropia->fecha_programada->format('Y-m-d'));
    }

    public function test_el_tecnico_no_borra_ordenes(): void
    {
        $this->actingAs($this->usuarioTecnicoConOrden());

        Livewire::test('pages::mantenimientos.index')
            ->call('confirmarEliminacion', $this->ordenPropia->id)
            ->assertForbidden();
    }

    public function test_un_reporte_ajeno_no_aparece_en_el_listado(): void
    {
        $this->ordenAjena->update(['estado' => 'ejecutado', 'fecha_ejecucion' => '2026-09-11']);
        Reporte::registrar($this->ordenAjena);

        $this->ordenPropia->update(['estado' => 'ejecutado', 'fecha_ejecucion' => '2026-09-10']);
        $propio = Reporte::registrar($this->ordenPropia);

        $this->actingAs($this->usuarioDeInstitucion());

        $this->assertSame([$propio->id], Reporte::pluck('id')->all());
    }

    // ------------------------------------------------------------------
    // Panel
    // ------------------------------------------------------------------

    /**
     * El panel cruza equipos con empresas y áreas en una misma consulta, y ahí
     * `id` y `empresa_id` existen en más de una tabla. El recorte tiene que
     * llegar con su tabla por delante o la consulta no resuelve.
     */
    public function test_el_panel_carga_con_cada_perfil_pese_a_los_cruces_de_tablas(): void
    {
        $tecnico = $this->usuarioTecnicoConOrden();

        $this->ordenPropia->update([
            'tipo' => 'correctivo',
            'motivo' => 'El equipo no enciende.',
            'fecha_programada' => Date::today()->subMonth(),
        ]);

        foreach ([$this->usuarioDeInstitucion(), $tecnico, User::factory()->create()] as $usuario) {
            $this->actingAs($usuario);

            Livewire::test('pages::panel.index')
                ->call('cargarAnalisis')
                ->assertOk();
        }
    }

    /**
     * Cada perfil ve sus propias cifras: la caché del panel no puede servirle a
     * uno lo que calculó para otro.
     */
    public function test_el_panel_no_comparte_cifras_entre_perfiles(): void
    {
        $this->actingAs($this->usuarioDeInstitucion());
        $propia = Livewire::test('pages::panel.index')->get('cronograma');

        $this->actingAs(User::factory()->create());
        $completo = Livewire::test('pages::panel.index')->get('cronograma');

        $this->assertSame(1, $propia['programadas']);
        $this->assertSame(2, $completo['programadas']);
    }

    // ------------------------------------------------------------------
    // Administrador
    // ------------------------------------------------------------------

    public function test_el_administrador_lo_sigue_viendo_todo(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertCount(2, Empresa::all());
        $this->assertCount(2, Equipo::all());
        $this->assertCount(2, Mantenimiento::all());
    }

    // ------------------------------------------------------------------
    // Apoyo
    // ------------------------------------------------------------------

    private function usuarioDeInstitucion(): User
    {
        return User::factory()->institucion($this->propia)->create();
    }

    private function usuarioTecnicoConOrden(): User
    {
        $tecnico = User::factory()->tecnico()->create(['name' => 'Luis Zapata']);

        $this->ordenPropia->forceFill([
            'tecnico_id' => $tecnico->id,
            'tecnico' => $tecnico->name,
        ])->save();

        return $tecnico;
    }
}
