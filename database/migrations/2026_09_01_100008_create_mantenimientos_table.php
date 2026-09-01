<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();

            // Asignación
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            // La empresa se copia del equipo al asignar: el listado filtra por
            // cliente sin tener que cruzar la tabla de equipos en cada consulta.
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();

            // Clasificación
            $table->string('tipo', 20);
            $table->string('estado', 20)->default('programado');
            $table->string('prioridad', 20)->nullable();

            // Programación y ejecución
            $table->date('fecha_programada');
            $table->date('fecha_ejecucion')->nullable();
            $table->string('tecnico')->nullable();

            // Detalle del trabajo
            $table->text('motivo')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('repuestos')->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('costo', 12, 2)->nullable();

            // Rutina ejecutada, heredada de la plantilla del equipo
            $table->json('subtareas')->nullable();
            $table->json('accesorios_estado')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('tipo');
            $table->index('estado');
            $table->index('fecha_programada');
            $table->index('tecnico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
