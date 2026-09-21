<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El técnico responsable deja de ser un nombre escrito a mano y pasa a ser un
 * usuario del sistema, que es quien podrá abrir la orden y cerrarla.
 *
 * La columna `tecnico` se conserva: es el nombre tal como salió impreso en el
 * reporte firmado, y ese documento no puede cambiar porque alguien renombre su
 * usuario después. Se mantiene sincronizada al guardar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->foreignId('tecnico_id')->nullable()->after('tecnico')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropForeign(['tecnico_id']);
            $table->dropColumn('tecnico_id');
        });
    }
};
