<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();

            // Asignación
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $table->foreignId('modelo_id')->nullable()->constrained('modelos')->nullOnDelete();

            // Información general
            $table->string('descripcion');
            $table->string('numero_serie')->nullable();
            $table->string('registro_invima')->nullable();
            $table->string('clasificacion_riesgo', 10)->nullable();
            $table->string('clasificacion_especialidad')->nullable();
            $table->string('fabricante')->nullable();
            $table->string('pais_origen')->nullable();
            $table->string('telefono_fabricante', 60)->nullable();
            $table->string('tipo_adquisicion', 40)->nullable();
            $table->string('prioridad', 20)->nullable();
            $table->text('observaciones_tecnicas')->nullable();
            $table->text('observaciones_generales')->nullable();
            $table->string('foto_path')->nullable();
            $table->text('mantenimiento')->nullable();
            $table->boolean('activo')->default(true);

            // Características técnicas de funcionamiento
            $table->string('suministro_electrico', 10)->default('ac');
            $table->string('voltaje', 40)->nullable();
            $table->string('amperaje', 40)->nullable();
            $table->string('frecuencia', 40)->nullable();
            $table->string('corriente', 40)->nullable();
            $table->string('potencia', 40)->nullable();
            $table->string('voltios', 40)->nullable();
            $table->string('temperatura', 40)->nullable();
            $table->string('presion', 40)->nullable();
            $table->string('peso', 40)->nullable();
            $table->string('velocidad', 40)->nullable();
            $table->string('tecnologia_predominante')->nullable();

            // Plantillas de mantenimiento
            $table->json('subtareas')->nullable();
            $table->json('accesorios_estado')->nullable();
            $table->text('componentes')->nullable();
            $table->text('observaciones_ot')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('descripcion');
            $table->index('numero_serie');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
