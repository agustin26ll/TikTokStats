<?php

namespace App\Application\Publicacion;

use App\Domain\Publicacion\Publicacion;

/**
 * DTO de Publicacion que cruza de Infraestructura a Application.
 *
 * Nunca un modelo Eloquent atraviesa esa frontera: el repositorio Eloquent
 * traduce los atributos a este DTO (o viceversa) y solo las entidades de
 * dominio / DTOs llegan a las capas superiores.
 */
final class PublicacionDTO
{
    /**
     * @param  int  $usuarioId  Identificador del usuario propietario.
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
     * @param  float  $retencionPorcentaje  Retención media en porcentaje.
     * @param  int  $guardados  Número de guardados.
     * @param  int  $compartidos  Número de compartidos.
     * @param  int  $comentarios  Número de comentarios.
     * @param  int  $nuevosSeguidores  Seguidores ganados.
     * @param  bool  $artistaNuevo  Indica si el artista es nuevo.
     * @param  string|null  $notas  Notas internas. Puede ser null.
     */
    public function __construct(
        public int $usuarioId,
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

    public static function desdeEntidad(Publicacion $publicacion): self
    {
        return new self(
            usuarioId: $publicacion->usuarioId,
            fecha: $publicacion->fecha,
            horaPublicacion: $publicacion->horaPublicacion,
            cancion: $publicacion->cancion,
            artista: $publicacion->artista,
            genero: $publicacion->genero,
            sistema: $publicacion->sistema,
            formato: $publicacion->formato,
            duracionSegundos: $publicacion->duracionSegundos,
            vistas: $publicacion->vistas,
            meGusta: $publicacion->meGusta,
            retencionPorcentaje: $publicacion->retencionPorcentaje,
            guardados: $publicacion->guardados,
            compartidos: $publicacion->compartidos,
            comentarios: $publicacion->comentarios,
            nuevosSeguidores: $publicacion->nuevosSeguidores,
            artistaNuevo: $publicacion->artistaNuevo,
            notas: $publicacion->notas,
        );
    }

    public function aEntidad(): Publicacion
    {
        return new Publicacion(
            usuarioId: $this->usuarioId,
            fecha: $this->fecha,
            horaPublicacion: $this->horaPublicacion,
            cancion: $this->cancion,
            artista: $this->artista,
            genero: $this->genero,
            sistema: $this->sistema,
            formato: $this->formato,
            duracionSegundos: $this->duracionSegundos,
            vistas: $this->vistas,
            meGusta: $this->meGusta,
            retencionPorcentaje: $this->retencionPorcentaje,
            guardados: $this->guardados,
            compartidos: $this->compartidos,
            comentarios: $this->comentarios,
            nuevosSeguidores: $this->nuevosSeguidores,
            artistaNuevo: $this->artistaNuevo,
            notas: $this->notas,
        );
    }

    /**
     * Atributos snake_case para persistir con Eloquent.
     *
     * @return array<string, string|int|float|bool|null>
     */
    public function aAtributos(): array
    {
        return [
            'usuario_id' => $this->usuarioId,
            'fecha' => $this->fecha,
            'hora_publicacion' => $this->horaPublicacion,
            'cancion' => $this->cancion,
            'artista' => $this->artista,
            'genero' => $this->genero,
            'sistema' => $this->sistema,
            'formato' => $this->formato,
            'duracion_segundos' => $this->duracionSegundos,
            'vistas' => $this->vistas,
            'me_gusta' => $this->meGusta,
            'retencion_porcentaje' => $this->retencionPorcentaje,
            'guardados' => $this->guardados,
            'compartidos' => $this->compartidos,
            'comentarios' => $this->comentarios,
            'nuevos_seguidores' => $this->nuevosSeguidores,
            'artista_nuevo' => $this->artistaNuevo,
            'notas' => $this->notas,
        ];
    }
}
