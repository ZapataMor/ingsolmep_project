<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // Fecha en que vence la garantía del equipo, para el filtro de
            // garantías (vigente / vencida / vence en 60 días).
            $table->date('garantia_vence')->nullable()->after('tipo_adquisicion');

            // Fecha del último mantenimiento ejecutado. Mientras no exista el
            // módulo de órdenes de trabajo, es el dato que alimenta la vista
            // «Sin mantenimiento hace más de 6 meses».
            $table->date('ultimo_mantenimiento')->nullable()->after('mantenimiento');

            $table->index('garantia_vence');
            $table->index('ultimo_mantenimiento');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex(['garantia_vence']);
            $table->dropIndex(['ultimo_mantenimiento']);
            $table->dropColumn(['garantia_vence', 'ultimo_mantenimiento']);
        });
    }
};
