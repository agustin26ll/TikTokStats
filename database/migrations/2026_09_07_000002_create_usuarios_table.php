<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('tiktok_username')->unique();
            $tabla->timestamp('created_at')->nullable();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
