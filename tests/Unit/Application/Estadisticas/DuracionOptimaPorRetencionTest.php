<?php

namespace Tests\Unit\Application\Estadisticas;

use App\Application\Estadisticas\DuracionOptimaPorRetencion;
use App\Application\Estadisticas\ResultadoEstadistica;
use App\Domain\Publicacion\Publicacion;
use PHPUnit\Framework\TestCase;

final class DuracionOptimaPorRetencionTest extends TestCase
{
    public function test_con_datos_validos_devuelve_rango_con_mayor_retencion_media(): void
    {
        $publicaciones = $this->fixturePublicaciones();

        $resultado = (new DuracionOptimaPorRetencion)->calcular($publicaciones);

        $this->assertInstanceOf(ResultadoEstadistica::class, $resultado);
        $this->assertSame('Duración óptima por retención', $resultado->nombre);
        // El fixture tiene:
        // 30s: retencion 41.7 (1 pub)
        // 45s: retencion 48.2 (1 pub)
        // 60s: retencion 52.3 (1 pub)
        // Cada uno en su bucket de 30s: 30-59, 60-89
        // El bucket 60-89 tiene 52.3 (el más alto)
        $this->assertStringContainsString('60', $resultado->valor);
        $this->assertStringContainsString('52.3', $resultado->detalle);
    }

    public function test_con_array_vacio_devuelve_sin_datos(): void
    {
        $resultado = (new DuracionOptimaPorRetencion)->calcular([]);

        $this->assertSame('Sin datos', $resultado->valor);
        $this->assertStringContainsString('No hay publicaciones', $resultado->detalle);
    }

    public function test_bucket_size_personalizado_cambia_el_rango_resultante(): void
    {
        // Fixture: duraciones 15, 40, 75 segundos
        // Con bucket 30: 15->0-29, 40->30-59, 75->60-89 → cada una en bucket distinto
        // Con bucket 50: 15->0-49, 40->0-49, 75->50-99 → 15 y 40 caen en mismo bucket
        $publicaciones = [
            $this->publicacion(15, 40.0),  // retención 40%
            $this->publicacion(40, 60.0),  // retención 60%
            $this->publicacion(75, 50.0),  // retención 50%
        ];

        // Bucket 30: media buckets son 40, 60, 50 → gana bucket 30-59 (60%)
        $resultado30 = (new DuracionOptimaPorRetencion(30))->calcular($publicaciones);
        $this->assertStringContainsString('30', $resultado30->valor);
        $this->assertStringContainsString('60.0', $resultado30->detalle);

        // Bucket 50: bucket 0-49 tiene (40+60)/2 = 50%, bucket 50-99 tiene 50% → empate, gana el primero (0-49)
        $resultado50 = (new DuracionOptimaPorRetencion(50))->calcular($publicaciones);
        $this->assertStringContainsString('0', $resultado50->valor);
        $this->assertStringContainsString('50.0', $resultado50->detalle);
    }

    private function publicacion(int $duracion, float $retencion): Publicacion
    {
        return new Publicacion(
            usuarioId: 1,
            fecha: '2024-01-15',
            horaPublicacion: '12:30:00',
            cancion: 'Test',
            artista: 'Test',
            genero: 'Pop',
            sistema: 'TikTok',
            formato: 'Video',
            duracionSegundos: $duracion,
            vistas: 1000,
            meGusta: 100,
            retencionPorcentaje: $retencion,
            guardados: 10,
            compartidos: 5,
            comentarios: 2,
            nuevosSeguidores: 1,
            artistaNuevo: false,
            notas: null,
        );
    }

    private function fixturePublicaciones(): array
    {
        return [
            new Publicacion(
                usuarioId: 1,
                fecha: '2024-01-15',
                horaPublicacion: '12:30:00',
                cancion: 'Canción Uno',
                artista: 'Artista Uno',
                genero: 'Pop',
                sistema: 'TikTok',
                formato: 'Video',
                duracionSegundos: 60,
                vistas: 150000,
                meGusta: 12000,
                retencionPorcentaje: 52.3,
                guardados: 800,
                compartidos: 500,
                comentarios: 300,
                nuevosSeguidores: 120,
                artistaNuevo: true,
                notas: null,
            ),
            new Publicacion(
                usuarioId: 1,
                fecha: '2024-02-20',
                horaPublicacion: '18:05:00',
                cancion: 'Tema Dos',
                artista: 'Artista Dos',
                genero: 'Rock',
                sistema: 'TikTok',
                formato: 'Foto',
                duracionSegundos: 30,
                vistas: 80000,
                meGusta: 8000,
                retencionPorcentaje: 41.7,
                guardados: 300,
                compartidos: 200,
                comentarios: 150,
                nuevosSeguidores: 60,
                artistaNuevo: false,
                notas: 'Notas varias',
            ),
            new Publicacion(
                usuarioId: 1,
                fecha: '2024-03-10',
                horaPublicacion: '09:15:00',
                cancion: 'Tema Tres',
                artista: 'Artista Tres',
                genero: 'Electrónica',
                sistema: 'Reels',
                formato: 'Video',
                duracionSegundos: 45,
                vistas: 60000,
                meGusta: 5000,
                retencionPorcentaje: 48.2,
                guardados: 200,
                compartidos: 100,
                comentarios: 80,
                nuevosSeguidores: 40,
                artistaNuevo: true,
                notas: null,
            ),
        ];
    }
}
