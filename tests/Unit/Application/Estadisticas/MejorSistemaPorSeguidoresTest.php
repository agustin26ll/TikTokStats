<?php

namespace Tests\Unit\Application\Estadisticas;

use App\Application\Estadisticas\MejorSistemaPorSeguidores;
use App\Application\Estadisticas\ResultadoEstadistica;
use App\Domain\Publicacion\Publicacion;
use PHPUnit\Framework\TestCase;

final class MejorSistemaPorSeguidoresTest extends TestCase
{
    public function test_con_datos_validos_devuelve_sistema_con_mayor_media(): void
    {
        $publicaciones = $this->fixturePublicaciones();

        $resultado = (new MejorSistemaPorSeguidores)->calcular($publicaciones);

        $this->assertInstanceOf(ResultadoEstadistica::class, $resultado);
        $this->assertSame('Mejor sistema por seguidores', $resultado->nombre);
        $this->assertSame('TikTok', $resultado->valor);
        $this->assertStringContainsString('TikTok', $resultado->detalle);
        $this->assertStringContainsString('90', $resultado->detalle); // media exacta del fixture (120+60)/2 = 90
    }

    public function test_con_array_vacio_devuelve_sin_datos(): void
    {
        $resultado = (new MejorSistemaPorSeguidores)->calcular([]);

        $this->assertSame('Sin datos', $resultado->valor);
        $this->assertStringContainsString('No hay publicaciones', $resultado->detalle);
    }

    private function fixturePublicaciones(): array
    {
        return [
            // TikTok: 2 publicaciones, nuevosSeguidores 120 + 60 = 180, media 90
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
            // Reels: 1 publicación, nuevosSeguidores 40, media 40
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
