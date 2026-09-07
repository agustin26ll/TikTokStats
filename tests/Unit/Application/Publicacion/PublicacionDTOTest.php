<?php

namespace Tests\Unit\Application\Publicacion;

use App\Application\Publicacion\PublicacionDTO;
use App\Domain\Publicacion\Publicacion;
use PHPUnit\Framework\TestCase;

final class PublicacionDTOTest extends TestCase
{
    public function test_desde_entidad_copia_todos_los_campos(): void
    {
        $entidad = $this->publicacionEjemplo();

        $dto = PublicacionDTO::desdeEntidad($entidad);

        $this->assertSame(42, $dto->usuarioId);
        $this->assertSame('2024-01-15', $dto->fecha);
        $this->assertSame('12:30:00', $dto->horaPublicacion);
        $this->assertSame('Canción Uno', $dto->cancion);
        $this->assertSame('Artista Uno', $dto->artista);
        $this->assertSame('Pop', $dto->genero);
        $this->assertSame('TikTok', $dto->sistema);
        $this->assertSame('Video', $dto->formato);
        $this->assertSame(60, $dto->duracionSegundos);
        $this->assertSame(150000, $dto->vistas);
        $this->assertSame(12000, $dto->meGusta);
        $this->assertSame(52.3, $dto->retencionPorcentaje);
        $this->assertSame(800, $dto->guardados);
        $this->assertSame(500, $dto->compartidos);
        $this->assertSame(300, $dto->comentarios);
        $this->assertSame(120, $dto->nuevosSeguidores);
        $this->assertTrue($dto->artistaNuevo);
        $this->assertSame('Notas internas', $dto->notas);
    }

    public function test_a_atributos_utiliza_claves_snake_case(): void
    {
        $dto = PublicacionDTO::desdeEntidad($this->publicacionEjemplo());

        $this->assertSame([
            'usuario_id' => 42,
            'fecha' => '2024-01-15',
            'hora_publicacion' => '12:30:00',
            'cancion' => 'Canción Uno',
            'artista' => 'Artista Uno',
            'genero' => 'Pop',
            'sistema' => 'TikTok',
            'formato' => 'Video',
            'duracion_segundos' => 60,
            'vistas' => 150000,
            'me_gusta' => 12000,
            'retencion_porcentaje' => 52.3,
            'guardados' => 800,
            'compartidos' => 500,
            'comentarios' => 300,
            'nuevos_seguidores' => 120,
            'artista_nuevo' => true,
            'notas' => 'Notas internas',
        ], $dto->aAtributos());
    }

    public function test_a_entidad_reconstruye_una_entidad_equivalente(): void
    {
        $entidad = $this->publicacionEjemplo();

        $reconstruida = PublicacionDTO::desdeEntidad($entidad)->aEntidad();

        $this->assertInstanceOf(Publicacion::class, $reconstruida);
        $this->assertEquals($entidad, $reconstruida);
    }

    public function test_notas_nulas_se_conservan_como_null(): void
    {
        $entidad = new Publicacion(
            usuarioId: 99,
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
        );

        $atributos = PublicacionDTO::desdeEntidad($entidad)->aAtributos();

        $this->assertArrayHasKey('notas', $atributos);
        $this->assertNull($atributos['notas']);
    }

    private function publicacionEjemplo(): Publicacion
    {
        return new Publicacion(
            usuarioId: 42,
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
            notas: 'Notas internas',
        );
    }
}
