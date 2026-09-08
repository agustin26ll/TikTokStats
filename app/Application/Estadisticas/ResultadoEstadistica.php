<?php

namespace App\Application\Estadisticas;

/**
 * DTO de resultado de una estadística.
 */
final class ResultadoEstadistica
{
    public function __construct(
        public string $nombre,
        public string|float|int $valor,
        public string $detalle,
    ) {}
}
