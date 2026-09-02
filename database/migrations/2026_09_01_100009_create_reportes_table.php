<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();

            // Una orden tiene un único reporte: volver a generarlo actualiza el
            // registro en vez de duplicar la fila del listado.
            $table->foreignId('mantenimiento_id')->unique()->constrained('mantenimientos')->cascadeOnDelete();
            // La empresa se copia de la orden, igual que en `mantenimientos`:
            // el listado filtra por cliente sin cruzar dos tablas más.
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();

            $table->string('tipo', 20);

            // Trazabilidad: quién lo generó la primera vez y quién la última.
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ultimo_generado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('generado_en');
            $table->timestamp('ultima_generacion');
            $table->unsignedInteger('veces_generado')->default(1);

            $table->timestamps();

            $table->index('tipo');
            $table->index('ultima_generacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
