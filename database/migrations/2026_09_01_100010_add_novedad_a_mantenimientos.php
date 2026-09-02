<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            // El técnico cierra un preventivo y deja constancia de que encontró
            // algo. Es el disparador de la bandeja «novedad sin correctivo»:
            // sin esta marca, el hallazgo se pierde dentro de las observaciones
            // y nadie le da seguimiento.
            $table->boolean('presenta_novedad')->default(false)->after('observaciones');
            $table->text('novedad')->nullable()->after('presenta_novedad');

            $table->index('presenta_novedad');

            // El cruce «¿este equipo tuvo un correctivo después de la novedad?»
            // recorre las órdenes de un equipo acotadas por tipo y fecha. Sin
            // este índice la subconsulta lee todas las órdenes del equipo.
            $table->index(['equipo_id', 'tipo', 'fecha_programada'], 'mantenimientos_equipo_tipo_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropIndex('mantenimientos_equipo_tipo_fecha_index');
            $table->dropIndex(['presenta_novedad']);
            $table->dropColumn(['presenta_novedad', 'novedad']);
        });
    }
};
