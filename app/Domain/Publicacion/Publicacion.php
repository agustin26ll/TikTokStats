<?php

namespace App\Domain\Publicacion;

/**
 * Entidad de dominio que representa una publicación de TikTok importada
 * desde el archivo Excel de estadísticas.
 *
 * Es una entidad de dominio pura: no hereda de Eloquent ni conoce la capa
 * de infraestructura. Las columnas coinciden con las del Excel real.
 */
final readonly class Publicacion
{
    /**
     * @param  string  $fecha  Fecha de publicación (ISO 8601, p. ej. 2024-01-15).
     * @param  string  $horaPublicacion  Hora de publicación (p. ej. 12:30:00).
     * @param  string  $cancion  Título de la canción.
     * @param  string  $artista  Nombre del artista.
     * @param  string  $genero  Género musical.
     * @param  string  $sistema  Sistema o plataforma (p. ej. TikTok, Reels).
     * @param  string  $formato  Formato de la publicación (p. ej. Video, Foto).
     * @param  int  $duracionSegundos  Duración del vídeo en segundos.
     * @param  int  $vistas  Número de vistas.
     * @param  int  $meGusta  Número de me gusta.
     * @param  float  $retencionPorcentaje  Retención media expresada en porcentaje.
     * @param  int  $guardados  Número de guardados.
     * @param  int  $compartidos  Número de compartidos.
     * @param  int  $comentarios  Número de comentarios.
     * @param  int  $nuevosSeguidores  Seguidores ganados con la publicación.
     * @param  bool  $artistaNuevo  Indica si el artista es nuevo.
     * @param  string|null  $notas  Notas internas. Puede estar vacío en el Excel real.
     */
    public function __construct(
        public string $fecha,
        public string $horaPublicacion,
        public string $cancion,
        public string $artista,
        public string $genero,
        public string $sistema,
        public string $formato,
        public int $duracionSegundos,
        public int $vistas,
        public int $meGusta,
        public float $retencionPorcentaje,
        public int $guardados,
        public int $compartidos,
        public int $comentarios,
        public int $nuevosSeguidores,
        public bool $artistaNuevo,
        public ?string $notas = null,
    ) {}
}
