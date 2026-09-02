<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El panel principal.
 *
 * Lo que se comprueba aquí, sobre todo, es la promesa que sostiene la pantalla:
 * cada cifra coincide con el listado al que enlaza. Por eso varias pruebas
 * cuentan dos veces la misma situación, una desde el panel y otra desde el
 * listado, y exigen el mismo número.
 */
class PanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El panel cachea cada bloque; sin vaciar, una prueba leería las cifras
        // que dejó la anterior.
        Cache::flush();

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Apoyos
    // ------------------------------------------------------------------

    private function empresa(string $nombre = 'Clínica de prueba'): Empresa
    {
        return Empresa::create(['nombre' => $nombre]);
    }

    /**
     * Equipo con los tres datos que el panel exige para no considerarlo
     * incompleto, de modo que en la bandeja sólo aparezca lo que cada prueba
     * prepara a propósito.
     *
     * @param  array<string, mixed>  $atributos
     */
    private function equipo(Empresa $empresa, array $atributos = []): Equipo
    {
        static $consecutivo = 0;
        $consecutivo++;

        $area = Area::firstOrCreate(['empresa_id' => $empresa->id, 'nombre' => 'Urgencias']);

        return Equipo::create(array_merge([
            'empresa_id' => $empresa->id,
            'area_id' => $area->id,
            'descripcion' => 'Monitor de signos vitales',
            'numero_serie' => 'SN-'.str_pad((string) $consecutivo, 5, '0', STR_PAD_LEFT),
            'registro_invima' => 'INVIMA-'.$consecutivo,
            'ultimo_mantenimiento' => Date::today()->subDays(10),
        ], $atributos));
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function orden(Equipo $equipo, array $atributos): Mantenimiento
    {
        return Mantenimiento::create(array_merge([
            'equipo_id' => $equipo->id,
            'empresa_id' => $equipo->empresa_id,
        ], $atributos));
    }

    /** @return array<string, mixed>|null */
    private function grupoDeBandeja(string $clave): ?array
    {
        foreach (Livewire::test('pages::panel.index')->instance()->bandeja as $grupo) {
            if ($grupo['clave'] === $clave) {
                return $grupo;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Acceso
    // ------------------------------------------------------------------

    public function test_el_panel_exige_haber_iniciado_sesion(): void
    {
        auth()->logout();

        $this->get(route('panel'))->assertRedirect(route('login'));
    }

    public function test_el_panel_es_la_pantalla_de_aterrizaje(): void
    {
        $this->get(route('panel'))->assertOk();
    }

    // ------------------------------------------------------------------
    // Indicadores principales
    // ------------------------------------------------------------------

    public function test_el_cumplimiento_muestra_su_fraccion_y_el_mes_anterior(): void
    {
        $equipo = $this->equipo($this->empresa());
        $hoy = Date::today();
        $mes = $hoy->startOfMonth();
        $anterior = $hoy->subMonth()->startOfMonth();

        // Mes en curso: tres programadas, dos ejecutadas.
        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'ejecutado', 'fecha_programada' => $mes, 'fecha_ejecucion' => $mes]);
        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'ejecutado', 'fecha_programada' => $mes->addDay(), 'fecha_ejecucion' => $mes->addDay()]);
        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => $hoy->endOfMonth()]);

        // Mes anterior: dos programadas, una ejecutada.
        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'ejecutado', 'fecha_programada' => $anterior, 'fecha_ejecucion' => $anterior]);
        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => $anterior->addDay()]);

        $cronograma = Livewire::test('pages::panel.index')->instance()->cronograma;

        $this->assertSame(3, $cronograma['programadas']);
        $this->assertSame(2, $cronograma['ejecutadas']);
        $this->assertSame(67, $cronograma['porcentaje']);
        $this->assertSame(50, $cronograma['porcentajeAnterior']);
        $this->assertSame(100, $cronograma['variacionEjecutadas']);
    }

    public function test_las_ordenes_vencidas_coinciden_con_su_listado_enlazado(): void
    {
        $equipo = $this->equipo($this->empresa());
        $hoy = Date::today();

        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => $hoy->subDays(5)]);
        $this->orden($equipo, ['tipo' => 'correctivo', 'estado' => 'en_proceso', 'fecha_programada' => $hoy->subDays(3)]);
        // Ni la futura ni la ya ejecutada están vencidas.
        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => $hoy->addDays(5)]);
        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'ejecutado', 'fecha_programada' => $hoy->subDays(9), 'fecha_ejecucion' => $hoy->subDays(9)]);

        $vencidas = Livewire::test('pages::panel.index')->instance()->vencidas;

        $this->assertSame(2, $vencidas['total']);
        // El retraso se lee en días cumplidos, en positivo.
        $this->assertSame(5, $vencidas['diasDeLaMasAntigua']);

        $listado = Livewire::test('pages::mantenimientos.index')->set('soloVencidos', true);

        $this->assertSame(2, $listado->instance()->mantenimientos->total());
    }

    public function test_los_equipos_fuera_de_servicio_no_incluyen_los_dados_de_baja(): void
    {
        $empresa = $this->empresa();

        $this->equipo($empresa, ['estado_operativo' => 'fuera_servicio']);
        $this->equipo($empresa, ['estado_operativo' => 'dado_baja']);
        $this->equipo($empresa, ['estado_operativo' => 'operativo']);

        $this->assertSame(1, Livewire::test('pages::panel.index')->instance()->fueraDeServicio['total']);

        $listado = Livewire::test('pages::equipos.index')->set('filtroBandeja', 'fuera_servicio');

        $this->assertSame(1, $listado->instance()->equipos->total());
    }

    // ------------------------------------------------------------------
    // Bandeja de atención
    // ------------------------------------------------------------------

    public function test_los_grupos_vacios_de_la_bandeja_no_se_renderizan(): void
    {
        $this->equipo($this->empresa());

        $this->assertSame([], Livewire::test('pages::panel.index')->instance()->bandeja);
    }

    public function test_una_novedad_sin_correctivo_posterior_aparece_en_la_bandeja(): void
    {
        $equipo = $this->equipo($this->empresa());
        $hace30 = Date::today()->subDays(30);

        $this->orden($equipo, [
            'tipo' => 'preventivo', 'estado' => 'ejecutado',
            'fecha_programada' => $hace30, 'fecha_ejecucion' => $hace30,
            'presenta_novedad' => true, 'novedad' => 'Batería agotada.',
        ]);

        $grupo = $this->grupoDeBandeja('novedad');

        $this->assertNotNull($grupo);
        $this->assertSame(1, $grupo['conteo']);

        $listado = Livewire::test('pages::equipos.index')->set('filtroBandeja', 'novedad');

        $this->assertSame(1, $listado->instance()->equipos->total());
    }

    public function test_un_correctivo_posterior_a_la_novedad_cierra_el_hueco(): void
    {
        $equipo = $this->equipo($this->empresa());
        $hoy = Date::today();

        $this->orden($equipo, [
            'tipo' => 'preventivo', 'estado' => 'ejecutado',
            'fecha_programada' => $hoy->subDays(30), 'fecha_ejecucion' => $hoy->subDays(30),
            'presenta_novedad' => true,
        ]);

        $this->orden($equipo, ['tipo' => 'correctivo', 'estado' => 'programado', 'fecha_programada' => $hoy->subDays(20)]);

        $this->assertSame(0, Equipo::query()->conNovedadPendiente()->count());
    }

    public function test_un_correctivo_cancelado_no_cuenta_como_seguimiento(): void
    {
        $equipo = $this->equipo($this->empresa());
        $hoy = Date::today();

        $this->orden($equipo, [
            'tipo' => 'preventivo', 'estado' => 'ejecutado',
            'fecha_programada' => $hoy->subDays(30), 'fecha_ejecucion' => $hoy->subDays(30),
            'presenta_novedad' => true,
        ]);

        $this->orden($equipo, ['tipo' => 'correctivo', 'estado' => 'cancelado', 'fecha_programada' => $hoy->subDays(20)]);

        $this->assertSame(1, Equipo::query()->conNovedadPendiente()->count());
    }

    public function test_un_correctivo_anterior_a_la_novedad_no_cuenta_como_seguimiento(): void
    {
        $equipo = $this->equipo($this->empresa());
        $hoy = Date::today();

        $this->orden($equipo, [
            'tipo' => 'correctivo', 'estado' => 'ejecutado',
            'fecha_programada' => $hoy->subDays(60), 'fecha_ejecucion' => $hoy->subDays(60),
        ]);

        $this->orden($equipo, [
            'tipo' => 'preventivo', 'estado' => 'ejecutado',
            'fecha_programada' => $hoy->subDays(30), 'fecha_ejecucion' => $hoy->subDays(30),
            'presenta_novedad' => true,
        ]);

        $this->assertSame(1, Equipo::query()->conNovedadPendiente()->count());
    }

    public function test_los_correctivos_estancados_coinciden_con_su_listado_enlazado(): void
    {
        $equipo = $this->equipo($this->empresa());
        $hoy = Date::today();

        $this->orden($equipo, ['tipo' => 'correctivo', 'estado' => 'programado', 'fecha_programada' => $hoy->subDays(20)]);
        $this->orden($equipo, ['tipo' => 'correctivo', 'estado' => 'en_proceso', 'fecha_programada' => $hoy->subDays(40)]);
        // Dentro del plazo, y una ya cerrada: ninguna de las dos está estancada.
        $this->orden($equipo, ['tipo' => 'correctivo', 'estado' => 'programado', 'fecha_programada' => $hoy->subDays(5)]);
        $this->orden($equipo, ['tipo' => 'correctivo', 'estado' => 'ejecutado', 'fecha_programada' => $hoy->subDays(50), 'fecha_ejecucion' => $hoy->subDays(48)]);

        $this->assertSame(2, $this->grupoDeBandeja('estancados')['conteo']);

        $listado = Livewire::test('pages::mantenimientos.index')->set('filtroBandeja', 'estancados');

        $this->assertSame(2, $listado->instance()->mantenimientos->total());
    }

    public function test_las_garantias_por_vencer_excluyen_las_ya_vencidas(): void
    {
        $empresa = $this->empresa();
        $hoy = Date::today();

        $this->equipo($empresa, ['garantia_vence' => $hoy->addDays(30)]);
        $this->equipo($empresa, ['garantia_vence' => $hoy->addDays(90)]);
        $this->equipo($empresa, ['garantia_vence' => $hoy->subDays(5)]);

        $this->assertSame(1, $this->grupoDeBandeja('garantia')['conteo']);

        $listado = Livewire::test('pages::equipos.index')->set('filtroBandeja', 'garantia');

        $this->assertSame(1, $listado->instance()->equipos->total());
    }

    public function test_los_equipos_sin_mantenimiento_incluyen_los_que_nunca_han_tenido_uno(): void
    {
        $empresa = $this->empresa();
        $hoy = Date::today();

        $this->equipo($empresa, ['ultimo_mantenimiento' => $hoy->subDays(200)]);
        $this->equipo($empresa, ['ultimo_mantenimiento' => null]);
        $this->equipo($empresa, ['ultimo_mantenimiento' => $hoy->subDays(30)]);

        $this->assertSame(2, $this->grupoDeBandeja('sin_mantenimiento')['conteo']);
    }

    public function test_los_datos_incompletos_detectan_el_nulo_y_la_cadena_vacia(): void
    {
        $empresa = $this->empresa();

        $this->equipo($empresa, ['numero_serie' => null]);
        $this->equipo($empresa, ['registro_invima' => '']);
        $this->equipo($empresa, ['area_id' => null]);
        $this->equipo($empresa);

        $this->assertSame(3, $this->grupoDeBandeja('incompletos')['conteo']);

        $listado = Livewire::test('pages::equipos.index')->set('filtroBandeja', 'incompletos');

        $this->assertSame(3, $listado->instance()->equipos->total());
    }

    public function test_el_cronograma_sin_iniciar_solo_aparece_despues_del_dia_quince(): void
    {
        $empresa = $this->empresa();
        $equipo = $this->equipo($empresa);

        Date::setTestNow(Date::parse('2026-03-10'));

        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => '2026-03-20']);

        Cache::flush();
        $this->assertNull($this->grupoDeBandeja('cronograma'));

        Date::setTestNow(Date::parse('2026-03-16'));

        Cache::flush();
        $grupo = $this->grupoDeBandeja('cronograma');

        $this->assertNotNull($grupo);
        $this->assertSame(1, $grupo['conteo']);

        $listado = Livewire::test('pages::empresas.index')->set('filtroBandeja', 'cronograma');

        $this->assertSame(1, $listado->instance()->empresas->total());
    }

    public function test_una_institucion_que_ya_ejecuto_algo_no_sale_en_el_cronograma(): void
    {
        Date::setTestNow(Date::parse('2026-03-16'));

        $empresa = $this->empresa();
        $equipo = $this->equipo($empresa);

        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => '2026-03-20']);
        $this->orden($equipo, ['tipo' => 'preventivo', 'estado' => 'ejecutado', 'fecha_programada' => '2026-03-05', 'fecha_ejecucion' => '2026-03-05']);

        Cache::flush();

        $this->assertNull($this->grupoDeBandeja('cronograma'));
    }

    // ------------------------------------------------------------------
    // Carga diferida y presupuesto de consultas
    // ------------------------------------------------------------------

    public function test_las_graficas_y_la_tabla_no_se_calculan_hasta_que_se_piden(): void
    {
        $this->equipo($this->empresa());

        $componente = Livewire::test('pages::panel.index');

        $this->assertFalse($componente->get('analisisPedido'));

        $componente->call('cargarAnalisis');

        $this->assertTrue($componente->get('analisisPedido'));
        $this->assertCount(12, $componente->instance()->ordenesPorMes);
    }

    public function test_la_pantalla_completa_no_supera_las_quince_consultas(): void
    {
        $empresa = $this->empresa();
        $hoy = Date::today();

        foreach (range(1, 5) as $numero) {
            $equipo = $this->equipo($empresa);

            $this->orden($equipo, [
                'tipo' => 'preventivo', 'estado' => 'ejecutado',
                'fecha_programada' => $hoy->subDays($numero), 'fecha_ejecucion' => $hoy->subDays($numero),
            ]);
        }

        Cache::flush();

        $consultas = 0;
        DB::listen(function () use (&$consultas): void {
            $consultas++;
        });

        // El primer pintado más la carga diferida: la pantalla completa.
        Livewire::test('pages::panel.index')->call('cargarAnalisis');

        $this->assertLessThanOrEqual(15, $consultas, "La pantalla ejecutó {$consultas} consultas.");
    }

    // ------------------------------------------------------------------
    // Enlaces
    // ------------------------------------------------------------------

    public function test_cada_fila_de_la_bandeja_enlaza_a_un_listado_filtrado(): void
    {
        $empresa = $this->empresa();
        $hoy = Date::today();

        $this->equipo($empresa, ['numero_serie' => null, 'garantia_vence' => $hoy->addDays(20)]);
        $this->equipo($empresa, ['ultimo_mantenimiento' => null]);

        $bandeja = Livewire::test('pages::panel.index')->instance()->bandeja;

        $this->assertNotEmpty($bandeja);

        foreach ($bandeja as $grupo) {
            $this->assertStringContainsString('bandeja=', $grupo['url'], "El grupo {$grupo['clave']} no enlaza a un listado filtrado.");
        }
    }

    public function test_la_novedad_marcada_en_el_formulario_llega_a_la_bandeja(): void
    {
        $equipo = $this->equipo($this->empresa());
        $hoy = Date::today();

        Livewire::test('pages::mantenimientos.index')
            ->set('equipo_id', (string) $equipo->id)
            ->set('tipo', 'preventivo')
            ->set('estado', 'ejecutado')
            ->set('fecha_programada', $hoy->subDays(3)->format('Y-m-d'))
            ->set('fecha_ejecucion', $hoy->subDays(3)->format('Y-m-d'))
            ->set('descripcion', 'Rutina preventiva trimestral.')
            ->set('presenta_novedad', true)
            ->set('novedad', 'Cable de alimentación agrietado.')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertTrue(Mantenimiento::query()->firstOrFail()->presenta_novedad);

        Cache::flush();

        $this->assertSame(1, $this->grupoDeBandeja('novedad')['conteo']);
    }

    public function test_marcar_la_novedad_obliga_a_describirla(): void
    {
        $equipo = $this->equipo($this->empresa());

        Livewire::test('pages::mantenimientos.index')
            ->set('equipo_id', (string) $equipo->id)
            ->set('tipo', 'preventivo')
            ->set('estado', 'ejecutado')
            ->set('fecha_programada', Date::today()->format('Y-m-d'))
            ->set('fecha_ejecucion', Date::today()->format('Y-m-d'))
            ->set('descripcion', 'Rutina preventiva trimestral.')
            ->set('presenta_novedad', true)
            ->set('novedad', '')
            ->call('guardar')
            ->assertHasErrors('novedad');
    }

    public function test_los_filtros_del_listado_viajan_en_la_url(): void
    {
        Livewire::withQueryParams(['bandeja' => 'incompletos'])
            ->test('pages::equipos.index')
            ->assertSet('filtroBandeja', 'incompletos');

        Livewire::withQueryParams(['vencidos' => true])
            ->test('pages::mantenimientos.index')
            ->assertSet('soloVencidos', true);

        Livewire::withQueryParams(['bandeja' => 'cronograma'])
            ->test('pages::empresas.index')
            ->assertSet('filtroBandeja', 'cronograma');
    }
}
