<?php

namespace App\Domain\Usuario;

/**
 * Entidad de dominio que representa a un usuario de la aplicación.
 *
 * Por ahora solo contiene la identidad (id, username de TikTok y fecha de
 * alta). No incluye lógica de autenticación: eso llegará en una tarea
 * posterior.
 */
final readonly class Usuario
{
    public function __construct(
        public int $id,
        public string $tiktokUsername,
        public \DateTimeImmutable $createdAt,
    ) {}
}
