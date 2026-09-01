<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('email')->nullable()->after('nombre');
            $table->string('celular', 60)->nullable()->after('telefono');
            $table->string('whatsapp', 40)->nullable()->after('celular');
            $table->string('logo_path')->nullable()->after('whatsapp');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['email', 'celular', 'whatsapp', 'logo_path', 'deleted_at']);
        });
    }
};
