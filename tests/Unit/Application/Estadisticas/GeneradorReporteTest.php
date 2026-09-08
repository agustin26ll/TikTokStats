<?php

namespace Tests\Unit\Application\Estadisticas;

use App\Application\Estadisticas\DuracionOptimaPorRetencion;
use App\Application\Estadisticas\GeneradorReporte;
use App\Application\Estadisticas\MejorSistemaPorMeGusta;
use App\Application\Estadisticas\MejorSistemaPorSeguidores;
use App\Application\Estadisticas\ResultadoEstadistica;
use App\Domain\Publicacion\Publicacion;
use PHPUnit\Framework\TestCase;

final class GeneradorReporteTest extends TestCase
{
    public function test_ejecuta_todas_las_calculadoras_inyectadas(): void
    {
        $calculadoras = [
            new MejorSistemaPorMeGusta,
            new MejorSistemaPorSeguidores,
            new DuracionOptimaPorRetencion,
        ];

        $generador = new GeneradorReporte($calculadoras);
        $publicaciones = $this->fixturePublicaciones();

        $resultados = $generador->generar($publicaciones);

        $this->assertCount(3, $resultados);
        $this->assertContainsOnlyInstancesOf(ResultadoEstadistica::class, $resultados);

        $nombres = array_column($resultados, 'nombre');
        $this->assertContains('Mejor sistema por me gusta', $nombres);
        $this->assertContains('Mejor sistema por seguidores', $nombres);
        $this->assertContains('Duración óptima por retención', $nombres);
    }

    public function test_con_calculadoras_vacias_devuelve_array_vacio(): void
    {
        $generador = new GeneradorReporte([]);

        $resultados = $generador->generar($this->fixturePublicaciones());

        $this->assertSame([], $resultados);
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
