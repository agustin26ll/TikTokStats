<?php

namespace App\Infrastructure\Repositorios;

use App\Application\Publicacion\PublicacionDTO;
use App\Domain\Publicacion\Publicacion;
use App\Domain\Publicacion\PublicacionRepositorioInterface;
use App\Infrastructure\Modelos\PublicacionModelo;

/**
 * Repositorio Eloquent de publicaciones.
 *
 * Traduce entre la entidad de dominio (Publicacion) y el modelo Eloquent
 * usando PublicacionDTO como vehículo: ningún Eloquent sale de esta capa.
 */
final class PublicacionRepositorioEloquent implements PublicacionRepositorioInterface
{
    public function __construct(
        private readonly PublicacionModelo $modelo,
    ) {}

    public function guardar(Publicacion $publicacion): void
    {
        $atributos = PublicacionDTO::desdeEntidad($publicacion)->aAtributos();

        $this->modelo->newQuery()->create($atributos);
    }

    public function guardarVarias(array $publicaciones): void
    {
        if ($publicaciones === []) {
            return;
        }

        $atributos = array_map(
            static fn (Publicacion $publicacion): array => PublicacionDTO::desdeEntidad($publicacion)->aAtributos(),
            $publicaciones,
        );

        $this->modelo->newQuery()->insert($atributos);
    }

    public function obtenerTodas(): array
    {
        return $this->modelo->newQuery()
            ->get()
            ->map(fn (PublicacionModelo $registro): Publicacion => $this->entidadDesdeModelo($registro))
            ->all();
    }

    public function limpiar(): void
    {
        $this->modelo->newQuery()->delete();
    }

    private function entidadDesdeModelo(PublicacionModelo $registro): Publicacion
    {
        $dto = new PublicacionDTO(
            usuarioId: $registro->usuario_id,
            fecha: $registro->fecha,
            horaPublicacion: $registro->hora_publicacion,
            cancion: $registro->cancion,
            artista: $registro->artista,
            genero: $registro->genero,
            sistema: $registro->sistema,
            formato: $registro->formato,
            duracionSegundos: $registro->duracion_segundos,
            vistas: $registro->vistas,
            meGusta: $registro->me_gusta,
            retencionPorcentaje: $registro->retencion_porcentaje,
            guardados: $registro->guardados,
            compartidos: $registro->compartidos,
            comentarios: $registro->comentarios,
            nuevosSeguidores: $registro->nuevos_seguidores,
            artistaNuevo: $registro->artista_nuevo,
            notas: $registro->notas,
        );

        return $dto->aEntidad();
    }
}
