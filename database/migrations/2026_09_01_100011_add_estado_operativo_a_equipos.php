<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // `activo` dice si el equipo sigue en el inventario; esto dice si
            // está prestando servicio. Son ejes distintos: un equipo dado de
            // baja no es un problema abierto, uno fuera de servicio sí, y el
            // panel sólo debe alertar por los segundos.
            $table->string('estado_operativo', 20)->default('operativo')->after('activo');

            $table->index('estado_operativo');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex(['estado_operativo']);
            $table->dropColumn('estado_operativo');
        });
    }
};
