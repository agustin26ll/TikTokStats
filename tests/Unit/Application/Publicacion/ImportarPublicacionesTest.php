<?php

namespace Tests\Unit\Application\Publicacion;

use App\Application\Publicacion\ImportarPublicaciones;
use App\Domain\Publicacion\Publicacion;
use App\Domain\Publicacion\PublicacionRepositorioInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class ImportarPublicacionesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_importar_guarda_publicaciones_y_devuelve_el_total(): void
    {
        $repositorio = Mockery::mock(PublicacionRepositorioInterface::class);
        $publicaciones = [
            $this->publicacionEjemplo('2024-01-15', 'Canción Uno'),
            $this->publicacionEjemplo('2024-02-20', 'Tema Dos'),
        ];

        $repositorio
            ->shouldReceive('guardarVarias')
            ->once()
            ->withArgs(fn (array $recibido): bool => $recibido === $publicaciones);

        $servicio = new ImportarPublicaciones($repositorio);

        $this->assertSame(2, $servicio->importar($publicaciones));
    }

    public function test_importar_con_lista_vacia_devuelve_cero(): void
    {
        $repositorio = Mockery::mock(PublicacionRepositorioInterface::class);
        $repositorio->shouldReceive('guardarVarias')->once()->with([]);

        $servicio = new ImportarPublicaciones($repositorio);

        $this->assertSame(0, $servicio->importar([]));
    }

    private function publicacionEjemplo(string $fecha, string $cancion): Publicacion
    {
        return new Publicacion(
            usuarioId: 1,
            fecha: $fecha,
            horaPublicacion: '12:30:00',
            cancion: $cancion,
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
        );
    }
}
