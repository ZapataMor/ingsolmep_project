<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmpresasTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('empresas.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_empresas(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('empresas.index'));
        $response->assertOk();
    }

    public function test_una_empresa_puede_registrarse(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::empresas.index')
            ->call('abrirCreacion')
            ->set('nombre', 'I.P.S.I. ANASU AINWAA')
            ->set('nit', '900203322-3')
            ->set('email', 'anasuainwaaipsi@gmail.com')
            ->set('ciudad', 'MAICAO - LA GUAJIRA')
            ->set('celular', '3206415286')
            ->set('whatsapp', '573206415286')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSet('mostrarFormulario', false);

        $this->assertDatabaseHas('empresas', [
            'nombre' => 'I.P.S.I. ANASU AINWAA',
            'nit' => '900203322-3',
            'email' => 'anasuainwaaipsi@gmail.com',
            'whatsapp' => '573206415286',
            'activo' => true,
        ]);
    }

    public function test_el_formulario_exige_los_campos_obligatorios(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::empresas.index')
            ->call('abrirCreacion')
            ->call('guardar')
            ->assertHasErrors(['nombre', 'nit', 'email', 'celular']);

        $this->assertDatabaseCount('empresas', 0);
    }

    public function test_una_empresa_puede_editarse(): void
    {
        $this->actingAs(User::factory()->create());

        $empresa = Empresa::create([
            'nombre' => 'CRUZ ROJA SEDE MAICAO',
            'nit' => '8000067514-4',
            'email' => 'directivo.maicao@cruzrojacolombia.org',
            'ciudad' => 'MAICAO - LA GUAJIRA',
            'celular' => '3000000000',
        ]);

        Livewire::test('pages::empresas.index')
            ->call('editar', $empresa->id)
            ->assertSet('nombre', 'CRUZ ROJA SEDE MAICAO')
            ->set('ciudad', 'RIOHACHA - LA GUAJIRA')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('RIOHACHA - LA GUAJIRA', $empresa->fresh()->ciudad);
    }

    public function test_el_estado_puede_alternarse_desde_la_tabla(): void
    {
        $this->actingAs(User::factory()->create());

        $empresa = Empresa::create(['nombre' => 'Prueba 1', 'nit' => 'prueba1', 'activo' => true]);

        Livewire::test('pages::empresas.index')->call('alternarActivo', $empresa->id);

        $this->assertFalse($empresa->fresh()->activo);
    }

    public function test_una_empresa_se_elimina_de_forma_logica(): void
    {
        $this->actingAs(User::factory()->create());

        $empresa = Empresa::create(['nombre' => 'Test', 'nit' => 'test']);

        Livewire::test('pages::empresas.index')
            ->call('confirmarEliminacion', $empresa->id)
            ->assertSet('empresaAEliminar', $empresa->id)
            ->call('eliminar')
            ->assertSet('empresaAEliminar', null);

        $this->assertSoftDeleted($empresa);
    }

    public function test_el_buscador_filtra_por_nombre_nit_y_ciudad(): void
    {
        $this->actingAs(User::factory()->create());

        Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '111', 'ciudad' => 'Riohacha']);
        Empresa::create(['nombre' => 'IPS del Sur', 'nit' => '222', 'ciudad' => 'Maicao']);

        Livewire::test('pages::empresas.index')
            ->set('buscar', 'Maicao')
            ->assertSee('IPS del Sur')
            ->assertDontSee('Clínica del Norte');
    }

    public function test_la_ficha_de_la_empresa_lista_sus_equipos_asignados(): void
    {
        $this->actingAs(User::factory()->create());

        $propia = Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '111']);
        $ajena = Empresa::create(['nombre' => 'IPS del Sur', 'nit' => '222']);

        Equipo::create([
            'empresa_id' => $propia->id,
            'descripcion' => 'MONITOR DE SIGNOS VITALES',
            'numero_serie' => 'SN-0001',
        ]);

        Equipo::create([
            'empresa_id' => $ajena->id,
            'descripcion' => 'VENTILADOR MECANICO',
            'numero_serie' => 'SN-0002',
        ]);

        Livewire::test('pages::empresas.index')
            ->call('verEmpresa', $propia->id)
            ->call('verPestanaFicha', 'equipos')
            ->assertSee('Equipos asignados')
            ->assertSee('MONITOR DE SIGNOS VITALES')
            ->assertSee('SN-0001')
            ->assertDontSee('VENTILADOR MECANICO');
    }

    public function test_la_ficha_avisa_cuando_la_empresa_no_tiene_equipos(): void
    {
        $this->actingAs(User::factory()->create());

        $empresa = Empresa::create(['nombre' => 'Clínica sin inventario', 'nit' => '333']);

        Livewire::test('pages::empresas.index')
            ->call('verEmpresa', $empresa->id)
            ->call('verPestanaFicha', 'equipos')
            ->assertSee('Esta institución todavía no tiene equipos asignados en el inventario.', false);
    }

    public function test_la_ficha_abre_en_informacion_y_separa_equipos_y_usuarios_por_pestanas(): void
    {
        $this->actingAs(User::factory()->create());

        $empresa = Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '111']);

        Equipo::create([
            'empresa_id' => $empresa->id,
            'descripcion' => 'MONITOR DE SIGNOS VITALES',
            'numero_serie' => 'SN-0001',
        ]);

        User::factory()->create([
            'name' => 'Recepción Norte',
            'rol' => 'institucion',
            'empresa_id' => $empresa->id,
        ]);

        // Se comprueba con los títulos de sección, no con los datos: el nombre
        // o el correo de la empresa también salen en la tabla del fondo.
        $componente = Livewire::test('pages::empresas.index')->call('verEmpresa', $empresa->id);

        $componente
            ->assertSet('fichaPestana', 'informacion')
            ->assertSee('Identificación', false)
            ->assertDontSee('Equipos asignados')
            ->assertDontSee('Usuarios de la institución', false);

        $componente
            ->call('verPestanaFicha', 'equipos')
            ->assertSee('Equipos asignados')
            ->assertSee('MONITOR DE SIGNOS VITALES')
            ->assertDontSee('Identificación', false)
            ->assertDontSee('Usuarios de la institución', false);

        $componente
            ->call('verPestanaFicha', 'usuarios')
            ->assertSee('Usuarios de la institución', false)
            ->assertSee('Recepción Norte', false)
            ->assertDontSee('Identificación', false)
            ->assertDontSee('Equipos asignados');
    }

    public function test_una_pestana_inexistente_no_cambia_la_ficha(): void
    {
        $this->actingAs(User::factory()->create());

        $empresa = Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '111']);

        Livewire::test('pages::empresas.index')
            ->call('verEmpresa', $empresa->id)
            ->call('verPestanaFicha', 'mantenimientos')
            ->assertSet('fichaPestana', 'informacion');
    }

    public function test_cada_ficha_se_abre_por_su_primera_pestana(): void
    {
        $this->actingAs(User::factory()->create());

        $primera = Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '111']);
        $segunda = Empresa::create(['nombre' => 'IPS del Sur', 'nit' => '222']);

        Livewire::test('pages::empresas.index')
            ->call('verEmpresa', $primera->id)
            ->call('verPestanaFicha', 'usuarios')
            ->call('cerrarDetalle')
            ->call('verEmpresa', $segunda->id)
            ->assertSet('fichaPestana', 'informacion');
    }
}
