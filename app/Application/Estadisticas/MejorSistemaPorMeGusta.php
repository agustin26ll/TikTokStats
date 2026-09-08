<?php

namespace App\Application\Estadisticas;

/**
 * Calcula el sistema (TikTok, Reels, etc.) con mayor media de me_gusta
 * por publicación.
 */
final class MejorSistemaPorMeGusta implements CalculadoraEstadistica
{
    public function calcular(array $publicaciones): ResultadoEstadistica
    {
        if ($publicaciones === []) {
            return new ResultadoEstadistica(
                nombre: 'Mejor sistema por me gusta',
                valor: 'Sin datos',
                detalle: 'No hay publicaciones para analizar.',
            );
        }

        /** @var array<string, array{suma: int, cuenta: int}> $porSistema */
        $porSistema = [];

        foreach ($publicaciones as $publicacion) {
            $sistema = $publicacion->sistema;
            if (! isset($porSistema[$sistema])) {
                $porSistema[$sistema] = ['suma' => 0, 'cuenta' => 0];
            }
            $porSistema[$sistema]['suma'] += $publicacion->meGusta;
            $porSistema[$sistema]['cuenta']++;
        }

        $mejorSistema = '';
        $mejorMedia = -1.0;

        foreach ($porSistema as $sistema => $datos) {
            $media = $datos['suma'] / $datos['cuenta'];
            if ($media > $mejorMedia) {
                $mejorMedia = $media;
                $mejorSistema = $sistema;
            }
        }

        return new ResultadoEstadistica(
            nombre: 'Mejor sistema por me gusta',
            valor: $mejorSistema,
            detalle: sprintf(
                'El sistema "%s" tiene la mayor media de me gusta por publicación (%.1f de media sobre %d publicaciones).',
                $mejorSistema,
                $mejorMedia,
                $porSistema[$mejorSistema]['cuenta'],
            ),
        );
    }
}
