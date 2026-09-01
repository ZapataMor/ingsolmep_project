<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Create the credentials used to sign in while the system is in development.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'prueba.ingsolmep'],
            [
                'name' => 'Usuario de Prueba',
                'email' => 'prueba@ingsolmep.com',
                'email_verified_at' => now(),
                'password' => 'ingsolmep123',
            ],
        );
    }
}
