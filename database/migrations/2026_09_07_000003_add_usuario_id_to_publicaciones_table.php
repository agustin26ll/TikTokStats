<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración. Se crea una migración nueva (no se edita la
     * original de publicaciones) porque esa ya está aplicada en otros
     * entornos; esto evita desincronizaciones.
     */
    public function up(): void
    {
        Schema::table('publicaciones', function (Blueprint $tabla) {
            $tabla->foreignId('usuario_id')
                ->after('id')
                ->constrained('usuarios')
                ->cascadeOnDelete();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('publicaciones', function (Blueprint $tabla) {
            $tabla->dropConstrainedForeignId('usuario_id');
        });
    }
};
