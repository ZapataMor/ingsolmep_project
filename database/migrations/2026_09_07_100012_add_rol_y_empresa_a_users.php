<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los tres perfiles con los que se entra al sistema.
 *
 * El administrador es INGSOLMEP; la institución es la IPS cliente, que sólo
 * mira lo suyo; el técnico es quien ejecuta las órdenes que se le asignan.
 * Quien ya existía queda como administrador: es lo que era hasta ahora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('administrador')->after('username');

            // Sólo la tiene el usuario de institución: es la IPS por la que entra.
            $table->foreignId('empresa_id')->nullable()->after('rol')
                ->constrained('empresas')->nullOnDelete();

            $table->string('telefono', 60)->nullable()->after('email');

            // Un usuario que ya no debe entrar se desactiva, no se borra: su
            // nombre sigue colgando de las órdenes que ejecutó.
            $table->boolean('activo')->default(true)->after('telefono');

            $table->index('rol');
        });

        DB::table('users')->update(['rol' => 'administrador']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropIndex(['rol']);
            $table->dropColumn(['rol', 'empresa_id', 'telefono', 'activo']);
        });
    }
};
