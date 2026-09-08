<?php

namespace App\Application\Estadisticas;

/**
 * Ejecuta todas las calculadoras registradas y devuelve los resultados.
 *
 * Las calculadoras se inyectan via constructor (tagged binding en el provider).
 * Para añadir una nueva estadística basta con crear la clase y registrarla
 * en AppServiceProvider — ninguna otra capa cambia (OCP).
 */
final class GeneradorReporte
{
    /**
     * @param  list<CalculadoraEstadistica>  $calculadoras
     */
    public function __construct(
        private readonly array $calculadoras,
    ) {}

    /**
     * @return list<ResultadoEstadistica>
     */
    public function generar(array $publicaciones): array
    {
        $resultados = [];

        foreach ($this->calculadoras as $calculadora) {
            $resultados[] = $calculadora->calcular($publicaciones);
        }

        return $resultados;
    }
}
