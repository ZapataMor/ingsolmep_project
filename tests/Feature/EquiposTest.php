<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\User;
use App\Support\SemaforoMantenimiento;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Tests\TestCase;

class EquiposTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('equipos.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_equipos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('equipos.index'));
        $response->assertOk();
    }

    public function test_el_listado_solo_muestra_equipo_marca_empresa_y_estado(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipoDePrueba();
        $equipo->update([
            'numero_serie' => 'SN-77777',
            'observaciones_tecnicas' => 'Pantalla con rayón en la esquina',
            'mantenimiento' => 'Rutina trimestral pactada',
            'clasificacion_riesgo' => 'IIB',
        ]);

        // El resto de la hoja de vida sólo se ve al abrir la ficha, así que no
        // debe aparecer ni en tarjetas ni en tabla.
        foreach (['cards', 'tabla'] as $vista) {
            Livewire::test('pages::equipos.index')
                ->set('vista', $vista)
                ->assertSee('MONITOR DE SIGNOS VITALES')
                ->assertSee('MINDRAY')
                ->assertSee('BeneVision N1')
                ->assertSee('Clínica del Norte', false)
                ->assertSee('Activo')
                ->assertDontSee('SN-77777')
                ->assertDontSee('Pantalla con rayón en la esquina', false)
                ->assertDontSee('Rutina trimestral pactada')
                ->assertDontSee('Riesgo IIB');
        }
    }

    public function test_el_semaforo_toma_la_orden_abierta_mas_proxima(): void
    {
        $equipo = $this->equipoDePrueba();

        // Una orden ya ejecutada y otra cancelada no cuentan: lo que se anuncia
        // es lo que todavía está por hacerse.
        $this->orden($equipo, Date::today()->addDays(2), estado: 'ejecutado');
        $this->orden($equipo, Date::today()->addDays(4), estado: 'cancelado');
        $this->orden($equipo, Date::today()->addDays(20));
        $proxima = $this->orden($equipo, Date::today()->addDays(9));

        $semaforo = $equipo->fresh()->semaforoMantenimiento();

        $this->assertNotNull($semaforo);
        $this->assertSame($proxima->id, $semaforo->orden?->id);
        $this->assertSame(9, $semaforo->diasRestantes);
        $this->assertTrue($semaforo->programado());
    }

    public function test_el_semaforo_pasa_de_verde_a_amarillo_y_a_rojo_segun_lo_que_falte(): void
    {
        $niveles = [
            30 => SemaforoMantenimiento::VERDE,
            7 => SemaforoMantenimiento::VERDE,
            6 => SemaforoMantenimiento::AMARILLO,
            3 => SemaforoMantenimiento::AMARILLO,
            2 => SemaforoMantenimiento::ROJO,
            -5 => SemaforoMantenimiento::ROJO,
        ];

        foreach ($niveles as $dias => $esperado) {
            $equipo = $this->equipoDePrueba();
            $this->orden($equipo, Date::today()->addDays($dias));

            $this->assertSame($esperado, $equipo->fresh()->semaforoMantenimiento()?->nivel(), "A {$dias} días");
        }
    }

    public function test_un_mantenimiento_vencido_llena_la_barra_y_se_anuncia_como_tal(): void
    {
        $equipo = $this->equipoDePrueba();
        $equipo->update(['ultimo_mantenimiento' => Date::today()->subDays(100)]);
        $this->orden($equipo, Date::today()->subDays(4));

        $semaforo = $equipo->fresh()->semaforoMantenimiento();

        $this->assertNotNull($semaforo);
        $this->assertTrue($semaforo->vencido());
        $this->assertSame(100, $semaforo->porcentaje);
        $this->assertSame('Vencido hace 4 días', $semaforo->etiqueta());
    }

    public function test_sin_orden_abierta_la_fecha_se_estima_desde_el_ultimo_mantenimiento(): void
    {
        $equipo = $this->equipoDePrueba();
        $equipo->update(['ultimo_mantenimiento' => Date::today()->subDays(180 - 5)]);

        $semaforo = $equipo->fresh()->semaforoMantenimiento();

        $this->assertNotNull($semaforo);
        $this->assertFalse($semaforo->programado());
        $this->assertSame(5, $semaforo->diasRestantes);
        $this->assertSame(SemaforoMantenimiento::AMARILLO, $semaforo->nivel());
    }

    public function test_un_equipo_sin_ordenes_ni_historial_no_tiene_semaforo(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipoDePrueba();

        $this->assertNull($equipo->semaforoMantenimiento());

        Livewire::test('pages::equipos.index')->assertSee('Sin programar');
    }

    public function test_la_ficha_abre_en_informacion_y_lista_el_historial_en_su_pestana(): void
    {
        $this->actingAs(User::factory()->create());

        $equipo = $this->equipoDePrueba();
        $this->orden($equipo, Date::today()->subDays(30), estado: 'ejecutado', ejecucion: Date::today()->subDays(28));

        $componente = Livewire::test('pages::equipos.index')->call('verEquipo', $equipo->id);

        // Se comprueba con rótulos propios de cada pestaña: los del asistente de
        // registro, que vive en la misma pantalla, repiten varios de la ficha.
        $componente
            ->assertSet('fichaPestana', 'informacion')
            ->assertSee('Subtareas de mantenimiento', false)
            ->assertSee('Próximo mantenimiento', false)
            ->assertDontSee('Historial de mantenimientos', false);

        $componente
            ->call('verPestanaFicha', 'mantenimientos')
            ->assertSee('Historial de mantenimientos', false)
            ->assertSee('MP-00001')
            ->assertSee('Ejecutado')
            ->assertDontSee('Subtareas de mantenimiento', false);
    }

    public function test_el_historial_avisa_cuando_el_equipo_no_tiene_ordenes(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::equipos.index')
            ->call('verEquipo', $this->equipoDePrueba()->id)
            ->call('verPestanaFicha', 'mantenimientos')
            ->assertSee('A este equipo todavía no se le ha registrado ningún mantenimiento.', false);
    }

    public function test_una_pestana_inexistente_no_cambia_la_ficha(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::equipos.index')
            ->call('verEquipo', $this->equipoDePrueba()->id)
            ->call('verPestanaFicha', 'reportes')
            ->assertSet('fichaPestana', 'informacion');
    }

    public function test_cada_ficha_se_abre_por_su_primera_pestana(): void
    {
        $this->actingAs(User::factory()->create());

        $primero = $this->equipoDePrueba();
        $segundo = $this->equipoDePrueba('BOMBA DE INFUSIÓN');

        Livewire::test('pages::equipos.index')
            ->call('verEquipo', $primero->id)
            ->call('verPestanaFicha', 'mantenimientos')
            ->call('cerrarDetalle')
            ->call('verEquipo', $segundo->id)
            ->assertSet('fichaPestana', 'informacion');
    }

    private function equipoDePrueba(string $descripcion = 'MONITOR DE SIGNOS VITALES'): Equipo
    {
        $empresa = Empresa::firstOrCreate(['nombre' => 'Clínica del Norte'], ['nit' => '900111222-1']);
        $marca = Marca::firstOrCreate(['nombre' => 'MINDRAY']);
        $modelo = Modelo::firstOrCreate(['nombre' => 'BeneVision N1', 'marca_id' => $marca->id]);

        return Equipo::create([
            'empresa_id' => $empresa->id,
            'marca_id' => $marca->id,
            'modelo_id' => $modelo->id,
            'descripcion' => $descripcion,
        ]);
    }

    private function orden(
        Equipo $equipo,
        CarbonInterface $programada,
        string $estado = 'programado',
        ?CarbonInterface $ejecucion = null,
    ): Mantenimiento {
        return Mantenimiento::create([
            'equipo_id' => $equipo->id,
            'empresa_id' => $equipo->empresa_id,
            'tipo' => 'preventivo',
            'estado' => $estado,
            'fecha_programada' => $programada,
            'fecha_ejecucion' => $ejecucion,
        ]);
    }
}
