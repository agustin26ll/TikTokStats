<?php

namespace App\Application\Estadisticas;

use App\Domain\Publicacion\Publicacion;

/**
 * Contrato para cualquier calculadora de estadísticas.
 *
 * Cada estadística es una clase independiente que implementa esta interfaz.
 * No hay clase base compartida más allá de la interfaz (OCP puro).
 */
interface CalculadoraEstadistica
{
    /**
     * Calcula la estadística sobre el conjunto de publicaciones.
     *
     * @param  list<Publicacion>  $publicaciones
     */
    public function calcular(array $publicaciones): ResultadoEstadistica;
}
