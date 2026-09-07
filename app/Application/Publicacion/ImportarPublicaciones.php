<?php

namespace App\Application\Publicacion;

use App\Domain\Publicacion\Publicacion;
use App\Domain\Publicacion\PublicacionRepositorioInterface;

/**
 * Caso de uso de la capa de Application: persiste las publicaciones ya
 * leídas desde el Excel a través del repositorio (interfaz, nunca Eloquent).
 */
final class ImportarPublicaciones
{
    public function __construct(
        private readonly PublicacionRepositorioInterface $repositorio,
    ) {}

    /**
     * @param  list<Publicacion>  $publicaciones
     */
    public function importar(array $publicaciones): int
    {
        $this->repositorio->guardarVarias($publicaciones);

        return count($publicaciones);
    }
}
