<?php

namespace Tests\Feature;

use App\Models\Empresa;
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
}
