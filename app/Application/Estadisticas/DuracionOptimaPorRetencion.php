<?php

namespace App\Application\Estadisticas;

/**
 * Calcula la duración óptima (en segundos) basada en la mayor retención
 * promedio por rangos de duración.
 *
 * Agrupa las publicaciones en buckets configurables y devuelve el rango
 * con mayor retención media.
 */
final class DuracionOptimaPorRetencion implements CalculadoraEstadistica
{
    public function __construct(
        private readonly int $tamanoBucket = 30,
    ) {
        if ($this->tamanoBucket <= 0) {
            throw new \InvalidArgumentException('El tamaño del bucket debe ser positivo.');
        }
    }

    public function calcular(array $publicaciones): ResultadoEstadistica
    {
        if ($publicaciones === []) {
            return new ResultadoEstadistica(
                nombre: 'Duración óptima por retención',
                valor: 'Sin datos',
                detalle: 'No hay publicaciones para analizar.',
            );
        }

        /** @var array<int, array{suma: float, cuenta: int}> $buckets */
        $buckets = [];

        foreach ($publicaciones as $publicacion) {
            $bucketInicio = (int) floor($publicacion->duracionSegundos / $this->tamanoBucket) * $this->tamanoBucket;
            if (! isset($buckets[$bucketInicio])) {
                $buckets[$bucketInicio] = ['suma' => 0.0, 'cuenta' => 0];
            }
            $buckets[$bucketInicio]['suma'] += $publicacion->retencionPorcentaje;
            $buckets[$bucketInicio]['cuenta']++;
        }

        $mejorBucket = -1;
        $mejorMedia = -1.0;

        foreach ($buckets as $inicio => $datos) {
            if ($datos['cuenta'] === 0) {
                continue;
            }
            $media = $datos['suma'] / $datos['cuenta'];
            if ($media > $mejorMedia) {
                $mejorMedia = $media;
                $mejorBucket = $inicio;
            }
        }

        if ($mejorBucket === -1) {
            return new ResultadoEstadistica(
                nombre: 'Duración óptima por retención',
                valor: 'Sin datos',
                detalle: 'No hay buckets con datos válidos.',
            );
        }

        $fin = $mejorBucket + $this->tamanoBucket - 1;

        return new ResultadoEstadistica(
            nombre: 'Duración óptima por retención',
            valor: sprintf('%ds–%ds', $mejorBucket, $fin),
            detalle: sprintf(
                'El rango de duración %ds–%ds tiene la mayor retención media (%.1f%%) sobre %d publicaciones.',
                $mejorBucket,
                $fin,
                $mejorMedia,
                $buckets[$mejorBucket]['cuenta'],
            ),
        );
    }
}
