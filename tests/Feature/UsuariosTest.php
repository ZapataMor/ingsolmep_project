<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('usuarios.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_el_administrador_puede_abrir_el_modulo(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('usuarios.index'))->assertOk();
    }

    public function test_la_institucion_y_el_tecnico_no_alcanzan_el_modulo(): void
    {
        $empresa = Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '900111222-1']);

        $this->actingAs(User::factory()->institucion($empresa)->create());
        $this->get(route('usuarios.index'))->assertForbidden();

        $this->actingAs(User::factory()->tecnico()->create());
        $this->get(route('usuarios.index'))->assertForbidden();
    }

    public function test_un_tecnico_puede_crearse(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::usuarios.index')
            ->call('abrirCreacion')
            ->assertSet('rol', 'tecnico')
            ->set('name', 'Luis Carlos Castellar Fuentes')
            ->set('username', 'luis.castellar')
            ->set('email', 'luis.castellar@ingsolmep.com')
            ->set('password', 'ingsolmep123')
            ->set('password_confirmation', 'ingsolmep123')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSet('mostrarFormulario', false);

        $this->assertDatabaseHas('users', [
            'username' => 'luis.castellar',
            'rol' => 'tecnico',
            'empresa_id' => null,
            'activo' => true,
        ]);
    }

    public function test_un_usuario_de_institucion_exige_la_institucion(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::usuarios.index')
            ->call('abrirCreacion', 'institucion')
            ->set('name', 'Clínica del Norte')
            ->set('username', 'clinica.norte')
            ->set('email', 'accesos@clinicanorte.test')
            ->set('password', 'ingsolmep123')
            ->set('password_confirmation', 'ingsolmep123')
            ->call('guardar')
            ->assertHasErrors(['empresa_id']);
    }

    public function test_la_ficha_de_una_empresa_abre_la_creacion_de_su_acceso(): void
    {
        $this->actingAs(User::factory()->create());

        $empresa = Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '900111222-1']);

        Livewire::test('pages::usuarios.index', ['empresa' => (string) $empresa->id])
            ->call('abrirCreacion', 'institucion', $empresa->id)
            ->assertSet('rol', 'institucion')
            ->assertSet('empresa_id', (string) $empresa->id)
            ->set('name', 'Clínica del Norte')
            ->set('username', 'clinica.norte')
            ->set('email', 'accesos@clinicanorte.test')
            ->set('password', 'ingsolmep123')
            ->set('password_confirmation', 'ingsolmep123')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'username' => 'clinica.norte',
            'rol' => 'institucion',
            'empresa_id' => $empresa->id,
        ]);
    }

    public function test_cambiar_el_rol_a_tecnico_suelta_la_institucion(): void
    {
        $this->actingAs(User::factory()->create());

        $empresa = Empresa::create(['nombre' => 'Clínica del Norte', 'nit' => '900111222-1']);
        $usuario = User::factory()->institucion($empresa)->create();

        Livewire::test('pages::usuarios.index')
            ->call('editar', $usuario->id)
            ->assertSet('empresa_id', (string) $empresa->id)
            ->set('rol', 'tecnico')
            ->assertSet('empresa_id', '')
            ->call('guardar')
            ->assertHasNoErrors();

        $usuario->refresh();

        $this->assertSame('tecnico', $usuario->rol);
        $this->assertNull($usuario->empresa_id);
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $this->actingAs(User::factory()->create());

        $usuario = User::factory()->tecnico()->create();
        $anterior = $usuario->password;

        Livewire::test('pages::usuarios.index')
            ->call('editar', $usuario->id)
            ->set('name', 'Nombre Corregido')
            ->call('guardar')
            ->assertHasNoErrors();

        $usuario->refresh();

        $this->assertSame('Nombre Corregido', $usuario->name);
        $this->assertSame($anterior, $usuario->password);
    }

    public function test_el_acceso_se_quita_sin_borrar_la_cuenta(): void
    {
        $this->actingAs(User::factory()->create());

        $usuario = User::factory()->tecnico()->create();

        Livewire::test('pages::usuarios.index')->call('alternarActivo', $usuario->id);

        $this->assertFalse($usuario->refresh()->activo);
    }

    public function test_nadie_se_deja_a_si_mismo_sin_acceso(): void
    {
        $administrador = User::factory()->create();
        $this->actingAs($administrador);

        Livewire::test('pages::usuarios.index')
            ->call('alternarActivo', $administrador->id)
            ->call('confirmarEliminacion', $administrador->id)
            ->call('eliminar');

        $this->assertTrue($administrador->refresh()->activo);
        $this->assertDatabaseHas('users', ['id' => $administrador->id]);
    }

    public function test_una_cuenta_desactivada_no_entra(): void
    {
        $this->actingAs(User::factory()->inactivo()->create());

        $this->get(route('panel'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_un_usuario_puede_eliminarse(): void
    {
        $this->actingAs(User::factory()->create());

        $usuario = User::factory()->tecnico()->create();

        Livewire::test('pages::usuarios.index')
            ->call('confirmarEliminacion', $usuario->id)
            ->call('eliminar')
            ->assertSet('usuarioAEliminar', null);

        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
    }
}
