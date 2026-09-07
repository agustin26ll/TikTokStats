<?php

namespace App\Infrastructure\Modelos;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de la tabla `publicaciones`.
 *
 * Vive en la capa de Infraestructura y NO debe cruzar hacia Application:
 * cualquier conversión a entidad de dominio se hace a través de
 * PublicacionDTO en el repositorio.
 */
#[Fillable([
    'fecha',
    'hora_publicacion',
    'cancion',
    'artista',
    'genero',
    'sistema',
    'formato',
    'duracion_segundos',
    'vistas',
    'me_gusta',
    'retencion_porcentaje',
    'guardados',
    'compartidos',
    'comentarios',
    'nuevos_seguidores',
    'artista_nuevo',
    'notas',
])]
class PublicacionModelo extends Model
{
    protected $table = 'publicaciones';

    /**
     * Fecha y hora publicacion NO se transforman a Carbon: la entidad de
     * dominio las maneja como texto plano y así se conservan tal cual.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duracion_segundos' => 'integer',
            'vistas' => 'integer',
            'me_gusta' => 'integer',
            'retencion_porcentaje' => 'float',
            'guardados' => 'integer',
            'compartidos' => 'integer',
            'comentarios' => 'integer',
            'nuevos_seguidores' => 'integer',
            'artista_nuevo' => 'boolean',
        ];
    }
}
