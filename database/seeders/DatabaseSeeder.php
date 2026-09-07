<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TestUserSeeder::class);
        $this->call(EquiposDemoSeeder::class);
        $this->call(MantenimientosDemoSeeder::class);
        $this->call(PanelDemoSeeder::class);

        // Va al final: los técnicos se sacan de las órdenes ya sembradas y las
        // instituciones, de las empresas del inventario.
        $this->call(UsuariosDemoSeeder::class);
    }
}
