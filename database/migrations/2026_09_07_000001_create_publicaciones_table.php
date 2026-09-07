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
        Schema::create('publicaciones', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->date('fecha');
            // Elegimos string en lugar de time para hora_publicacion porque la
            // entidad guarda la hora como texto plano (p. ej. "12:30:00") y el
            // tipo time exigiría un cast adicional en Eloquent que podría
            // introducir una fecha base; con string el redondeo es exacto en
            // SQLite, MySQL y PostgreSQL.
            $tabla->string('hora_publicacion');
            $tabla->string('cancion');
            $tabla->string('artista');
            $tabla->string('genero');
            $tabla->string('sistema');
            $tabla->string('formato');
            $tabla->unsignedInteger('duracion_segundos');
            $tabla->unsignedBigInteger('vistas');
            $tabla->unsignedInteger('me_gusta');
            // Decimal(5,2) explícito: la retención media se guarda como valor
            // porcentual (p. ej. 52.30), máximo 999.99. No usar un numeric
            // genérico para mantener precisión en MySQL/PostgreSQL.
            $tabla->decimal('retencion_porcentaje', 5, 2);
            $tabla->unsignedInteger('guardados');
            $tabla->unsignedInteger('compartidos');
            $tabla->unsignedInteger('comentarios');
            $tabla->unsignedInteger('nuevos_seguidores');
            $tabla->boolean('artista_nuevo')->default(false);
            // Las celdas de notas vienen vacías en el Excel real: permitimos NULL.
            $tabla->string('notas')->nullable();
            $tabla->timestamps();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('publicaciones');
    }
};
