<?php

use App\Models\Area;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;

/**
 * Alta un usuario autenticado, que es la condición de toda la pantalla.
 */
function usuario(): User
{
    $usuario = User::factory()->create();
    test()->actingAs($usuario);

    return $usuario;
}

/**
 * Equipo mínimo con los tres datos que el panel exige para no considerarlo
 * incompleto, para que sólo salgan en la bandeja los casos que cada prueba
 * prepara a propósito.
 *
 * @param  array<string, mixed>  $atributos
 */
function equipoCompleto(Empresa $empresa, array $atributos = []): Equipo
{
    $area = Area::create(['empresa_id' => $empresa->id, 'nombre' => 'Urgencias']);

    return Equipo::create(array_merge([
        'empresa_id' => $empresa->id,
        'area_id' => $area->id,
        'descripcion' => 'Monitor de signos vitales',
        'numero_serie' => 'SN-'.fake()->unique()->numerify('######'),
        'registro_invima' => 'INVIMA-'.fake()->unique()->numerify('#####'),
        'ultimo_mantenimiento' => Date::today()->subDays(10),
    ], $atributos));
}

function empresa(): Empresa
{
    return Empresa::create(['nombre' => 'Clínica '.fake()->unique()->word()]);
}

beforeEach(function () {
    Cache::flush();
});

// ---------------------------------------------------------------------------
// Acceso
// ---------------------------------------------------------------------------

test('el panel exige haber iniciado sesión', function () {
    $this->get(route('panel'))->assertRedirect(route('login'));
});

test('el panel es la pantalla de aterrizaje del usuario autenticado', function () {
    usuario();

    $this->get(route('panel'))->assertOk();
});

// ---------------------------------------------------------------------------
// Indicadores principales
// ---------------------------------------------------------------------------

test('el cumplimiento del mes muestra su fracción, su meta y el mes anterior', function () {
    usuario();
    $equipo = equipoCompleto(empresa());
    $hoy = Date::today();

    // Mes en curso: tres programadas, dos ejecutadas.
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'ejecutado', 'fecha_programada' => $hoy->startOfMonth(), 'fecha_ejecucion' => $hoy->startOfMonth()]);
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'ejecutado', 'fecha_programada' => $hoy->startOfMonth()->addDay(), 'fecha_ejecucion' => $hoy->startOfMonth()->addDay()]);
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => $hoy->endOfMonth()]);

    // Mes anterior: dos programadas, una ejecutada.
    $anterior = $hoy->subMonth()->startOfMonth();
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'ejecutado', 'fecha_programada' => $anterior, 'fecha_ejecucion' => $anterior]);
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => $anterior->addDay()]);

    $cronograma = Livewire::test('pages::panel.index')->instance()->cronograma;

    expect($cronograma['programadas'])->toBe(3)
        ->and($cronograma['ejecutadas'])->toBe(2)
        ->and($cronograma['porcentaje'])->toBe(67)
        ->and($cronograma['porcentajeAnterior'])->toBe(50)
        ->and($cronograma['variacionEjecutadas'])->toBe(100);
});

test('las órdenes vencidas cuentan lo mismo que su listado enlazado', function () {
    usuario();
    $equipo = equipoCompleto(empresa());
    $hoy = Date::today();

    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => $hoy->subDays(5)]);
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'correctivo', 'estado' => 'en_proceso', 'fecha_programada' => $hoy->subDays(3)]);
    // Ni la futura ni la ya ejecutada están vencidas.
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'programado', 'fecha_programada' => $hoy->addDays(5)]);
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'ejecutado', 'fecha_programada' => $hoy->subDays(9), 'fecha_ejecucion' => $hoy->subDays(9)]);

    expect(Livewire::test('pages::panel.index')->instance()->vencidas)->toBe(2);

    // La misma cifra que devuelve el listado al que enlaza el indicador.
    $listado = Livewire::test('pages::mantenimientos.index')->set('soloVencidos', true);

    expect($listado->instance()->mantenimientos->total())->toBe(2);
});

test('los equipos fuera de servicio no incluyen los dados de baja', function () {
    usuario();
    $empresa = empresa();

    equipoCompleto($empresa, ['estado_operativo' => 'fuera_servicio']);
    equipoCompleto($empresa, ['estado_operativo' => 'dado_baja']);
    equipoCompleto($empresa, ['estado_operativo' => 'operativo']);

    expect(Livewire::test('pages::panel.index')->instance()->fueraDeServicio)->toBe(1);
});

// ---------------------------------------------------------------------------
// Bandeja de atención
// ---------------------------------------------------------------------------

test('los grupos vacíos de la bandeja no se renderizan', function () {
    usuario();
    equipoCompleto(empresa());

    $bandeja = Livewire::test('pages::panel.index')->instance()->bandeja;

    expect($bandeja)->toBe([]);
});

test('una novedad sin correctivo posterior aparece en la bandeja', function () {
    usuario();
    $equipo = equipoCompleto(empresa());
    $hoy = Date::today();

    Mantenimiento::create([
        'equipo_id' => $equipo->id,
        'tipo' => 'preventivo',
        'estado' => 'ejecutado',
        'fecha_programada' => $hoy->subDays(30),
        'fecha_ejecucion' => $hoy->subDays(30),
        'presenta_novedad' => true,
        'novedad' => 'Batería agotada.',
    ]);

    $grupo = collect(Livewire::test('pages::panel.index')->instance()->bandeja)
        ->firstWhere('clave', 'novedad');

    expect($grupo)->not->toBeNull()
        ->and($grupo['conteo'])->toBe(1);

    // El listado enlazado devuelve exactamente ese equipo.
    $listado = Livewire::test('pages::equipos.index')->set('filtroBandeja', 'novedad');

    expect($listado->instance()->equipos->total())->toBe(1);
});

test('un correctivo posterior a la novedad cierra el hueco', function () {
    usuario();
    $equipo = equipoCompleto(empresa());
    $hoy = Date::today();

    Mantenimiento::create([
        'equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'ejecutado',
        'fecha_programada' => $hoy->subDays(30), 'fecha_ejecucion' => $hoy->subDays(30),
        'presenta_novedad' => true,
    ]);

    Mantenimiento::create([
        'equipo_id' => $equipo->id, 'tipo' => 'correctivo', 'estado' => 'programado',
        'fecha_programada' => $hoy->subDays(20),
    ]);

    expect(Equipo::query()->conNovedadPendiente()->count())->toBe(0);
});

test('un correctivo cancelado no cuenta como seguimiento de la novedad', function () {
    usuario();
    $equipo = equipoCompleto(empresa());
    $hoy = Date::today();

    Mantenimiento::create([
        'equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'ejecutado',
        'fecha_programada' => $hoy->subDays(30), 'fecha_ejecucion' => $hoy->subDays(30),
        'presenta_novedad' => true,
    ]);

    Mantenimiento::create([
        'equipo_id' => $equipo->id, 'tipo' => 'correctivo', 'estado' => 'cancelado',
        'fecha_programada' => $hoy->subDays(20),
    ]);

    expect(Equipo::query()->conNovedadPendiente()->count())->toBe(1);
});

test('un correctivo anterior a la novedad no cuenta como seguimiento', function () {
    usuario();
    $equipo = equipoCompleto(empresa());
    $hoy = Date::today();

    Mantenimiento::create([
        'equipo_id' => $equipo->id, 'tipo' => 'correctivo', 'estado' => 'ejecutado',
        'fecha_programada' => $hoy->subDays(60), 'fecha_ejecucion' => $hoy->subDays(60),
    ]);

    Mantenimiento::create([
        'equipo_id' => $equipo->id, 'tipo' => 'preventivo', 'estado' => 'ejecutado',
        'fecha_programada' => $hoy->subDays(30), 'fecha_ejecucion' => $hoy->subDays(30),
        'presenta_novedad' => true,
    ]);

    expect(Equipo::query()->conNovedadPendiente()->count())->toBe(1);
});

test('los correctivos estancados cuentan lo mismo que su listado enlazado', function () {
    usuario();
    $equipo = equipoCompleto(empresa());
    $hoy = Date::today();

    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'correctivo', 'estado' => 'programado', 'fecha_programada' => $hoy->subDays(20)]);
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'correctivo', 'estado' => 'en_proceso', 'fecha_programada' => $hoy->subDays(40)]);
    // Dentro del plazo, y una ya cerrada: ninguna de las dos está estancada.
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'correctivo', 'estado' => 'programado', 'fecha_programada' => $hoy->subDays(5)]);
    Mantenimiento::create(['equipo_id' => $equipo->id, 'tipo' => 'correctivo', 'estado' => 'ejecutado', 'fecha_programada' => $hoy->subDays(50), 'fecha_ejecucion' => $hoy->subDays(48)]);

    $grupo = collect(Livewire::test('pages::panel.index')->instance()->bandeja)
        ->firstWhere('clave', 'estancados');

    expect($grupo['conteo'])->toBe(2);

    $listado = Livewire::test('pages::mantenimientos.index')->set('filtroBandeja', 'estancados');

    expect($listado->instance()->mantenimientos->total())->toBe(2);
});

test('las garantías por vencer excluyen las ya vencidas', function () {
    usuario();
    $empresa = empresa();
    $hoy = Date::today();

    equipoCompleto($empresa, ['garantia_vence' => $hoy->addDays(30)]);
    equipoCompleto($empresa, ['garantia_vence' => $hoy->addDays(90)]);
    equipoCompleto($empresa, ['garantia_vence' => $hoy->subDays(5)]);

    $grupo = collect(Livewire::test('pages::panel.index')->instance()->bandeja)
        ->firstWhere('clave', 'garantia');

    expect($grupo['conteo'])->toBe(1);
});

test('los equipos sin mantenimiento incluyen los que nunca han tenido uno', function () {
    usuario();
    $empresa = empresa();
    $hoy = Date::today();

    equipoCompleto($empresa, ['ultimo_mantenimiento' => $hoy->subDays(200)]);
    equipoCompleto($empresa, ['ultimo_mantenimiento' => null]);
    equipoCompleto($empresa, ['ultimo_mantenimiento' => $hoy->subDays(30)]);

    $grupo = collect(Livewire::test('pages::panel.index')->instance()->bandeja)
        ->firstWhere('clave', 'sin_mantenimiento');

    expect($grupo['conteo'])->toBe(2);
});

test('los datos incompletos detectan tanto el nulo como la cadena vacía', function () {
    usuario();
    $empresa = empresa();

    equipoCompleto($empresa, ['numero_serie' => null]);
    equipoCompleto($empresa, ['registro_invima' => '']);
    equipoCompleto($empresa, ['area_id' => null]);
    equipoCompleto($empresa);

    $grupo = collect(Livewire::test('pages::panel.index')->instance()->bandeja)
        ->firstWhere('clave', 'incompletos');

    expect($grupo['conteo'])->toBe(3);

    $listado = Livewire::test('pages::equipos.index')->set('filtroBandeja', 'incompletos');

    expect($listado->instance()->equipos->total())->toBe(3);
});

test('el cronograma sin iniciar sólo aparece después del día 15', function () {
    usuario();
    $empresa = empresa();
    $equipo = equipoCompleto($empresa);

    $claves = function () {
        Cache::flush();

        return collect(Livewire::test('pages::panel.index')->instance()->bandeja)->pluck('clave');
    };

    Date::setTestNow(Date::parse('2026-03-10'));

    Mantenimiento::create([
        'equipo_id' => $equipo->id, 'empresa_id' => $empresa->id,
        'tipo' => 'preventivo', 'estado' => 'programado',
        'fecha_programada' => '2026-03-20',
    ]);

    expect($claves())->not->toContain('cronograma');

    Date::setTestNow(Date::parse('2026-03-16'));

    expect($claves())->toContain('cronograma');

    Date::setTestNow();
});

// ---------------------------------------------------------------------------
// Carga diferida y presupuesto de consultas
// ---------------------------------------------------------------------------

test('las gráficas y la tabla no se calculan hasta que se piden', function () {
    usuario();
    equipoCompleto(empresa());

    $componente = Livewire::test('pages::panel.index');

    expect($componente->get('analisisPedido'))->toBeFalse();

    $componente->call('cargarAnalisis');

    expect($componente->get('analisisPedido'))->toBeTrue()
        ->and($componente->instance()->ordenesPorMes)->toHaveCount(12);
});

test('la pantalla completa no supera las quince consultas', function () {
    usuario();
    $empresa = empresa();
    $hoy = Date::today();

    foreach (range(1, 5) as $numero) {
        $equipo = equipoCompleto($empresa);

        Mantenimiento::create([
            'equipo_id' => $equipo->id, 'empresa_id' => $empresa->id,
            'tipo' => 'preventivo', 'estado' => 'ejecutado',
            'fecha_programada' => $hoy->subDays($numero), 'fecha_ejecucion' => $hoy->subDays($numero),
        ]);
    }

    Cache::flush();

    $consultas = 0;
    DB::listen(function () use (&$consultas) {
        $consultas++;
    });

    // El primer pintado más la carga diferida: la pantalla completa.
    Livewire::test('pages::panel.index')->call('cargarAnalisis');

    expect($consultas)->toBeLessThanOrEqual(15);
});

// ---------------------------------------------------------------------------
// Enlaces
// ---------------------------------------------------------------------------

test('cada fila de la bandeja enlaza a un listado con el filtro aplicado', function () {
    usuario();
    $empresa = empresa();
    $hoy = Date::today();

    equipoCompleto($empresa, ['numero_serie' => null, 'garantia_vence' => $hoy->addDays(20)]);

    foreach (Livewire::test('pages::panel.index')->instance()->bandeja as $grupo) {
        expect($grupo['url'])->toContain('bandeja=');
    }
});

test('los filtros del listado viajan en la url', function () {
    usuario();

    Livewire::withQueryParams(['bandeja' => 'incompletos'])
        ->test('pages::equipos.index')
        ->assertSet('filtroBandeja', 'incompletos');

    Livewire::withQueryParams(['vencidos' => true])
        ->test('pages::mantenimientos.index')
        ->assertSet('soloVencidos', true);
});
