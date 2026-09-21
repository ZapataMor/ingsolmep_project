<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_login(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }

    /**
     * La raíz mandaba a todo el mundo a /login, y el middleware `guest` de
     * Fortify devolvía a la sesión ya iniciada a la ruta `home`, que es la raíz:
     * el navegador quedaba rebotando entre las dos sin llegar a ninguna parte.
     */
    public function test_root_redirects_authenticated_user_to_panel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('panel'));
    }
}
