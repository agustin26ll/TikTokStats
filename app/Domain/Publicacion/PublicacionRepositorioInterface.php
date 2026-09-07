<?php

namespace App\Domain\Publicacion;

/**
 * Contrato de persistencia de publicaciones.
 *
 * La implementación concreta vive en la capa de Infraestructura y nunca es
 * invocada desde los controladores: solo a través de esta interfaz
 * (principio de inversión de dependencias).
 */
interface PublicacionRepositorioInterface
{
    /**
     * Persiste una única publicación.
     */
    public function guardar(Publicacion $publicacion): void;

    /**
     * Persiste un conjunto de publicaciones de una sola operación.
     *
     * @param  list<Publicacion>  $publicaciones
     */
    public function guardarVarias(array $publicaciones): void;

    /**
     * Devuelve todas las publicaciones persistidas.
     *
     * @return list<Publicacion>
     */
    public function obtenerTodas(): array;

    /**
     * Elimina todas las publicaciones persistidas.
     * Útil antes de una reimportación del mismo Excel.
     */
    public function limpiar(): void;
}
