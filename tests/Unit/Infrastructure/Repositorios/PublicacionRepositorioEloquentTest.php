<?php

namespace Tests\Unit\Infrastructure\Repositorios;

use App\Application\Publicacion\PublicacionDTO;
use App\Domain\Publicacion\Publicacion;
use App\Infrastructure\Modelos\PublicacionModelo;
use App\Infrastructure\Repositorios\PublicacionRepositorioEloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

final class PublicacionRepositorioEloquentTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var PublicacionModelo&MockInterface */
    private MockInterface $modelo;

    /** @var Builder&MockInterface */
    private MockInterface $consultas;

    private PublicacionRepositorioEloquent $repositorio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelo = Mockery::mock(PublicacionModelo::class);
        $this->consultas = Mockery::mock(Builder::class);
        $this->modelo->shouldReceive('newQuery')->andReturn($this->consultas);

        $this->repositorio = new PublicacionRepositorioEloquent($this->modelo);
    }

    public function test_guardar_crea_un_registro_con_atributos_snake_case(): void
    {
        $publicacion = $this->publicacionEjemplo();
        $atributosEsperados = PublicacionDTO::desdeEntidad($publicacion)->aAtributos();

        $this->consultas->shouldReceive('create')->once()->with($atributosEsperados);

        $this->repositorio->guardar($publicacion);
    }

    public function test_guardar_varias_inserta_todas_las_publicaciones(): void
    {
        $publicacion1 = $this->publicacionEjemplo();
        $publicacion2 = $this->publicacionEjemplo('2024-02-20', 'Tema Dos');
        $atributosEsperados = [
            PublicacionDTO::desdeEntidad($publicacion1)->aAtributos(),
            PublicacionDTO::desdeEntidad($publicacion2)->aAtributos(),
        ];

        $this->consultas->shouldReceive('insert')->once()->with($atributosEsperados);

        $this->repositorio->guardarVarias([$publicacion1, $publicacion2]);
    }

    public function test_guardar_varias_con_lista_vacia_no_toca_la_base_de_datos(): void
    {
        $this->consultas->shouldNotReceive('insert');

        $this->repositorio->guardarVarias([]);
    }

    public function test_limpiar_elimina_todos_los_registros(): void
    {
        $this->consultas->shouldReceive('delete')->once();

        $this->repositorio->limpiar();
    }

    public function test_obtener_todas_devuelve_entidades_de_dominio(): void
    {
        $registro1 = $this->modeloConAtributos([
            'usuario_id' => 1,
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
        ]);
        $registro2 = $this->modeloConAtributos([
            'usuario_id' => 1,
            'fecha' => '2024-02-20',
            'hora_publicacion' => '18:05:00',
            'cancion' => 'Tema Dos',
            'artista' => 'Artista Dos',
            'genero' => 'Rock',
            'sistema' => 'Reels',
            'formato' => 'Foto',
            'duracion_segundos' => 30,
            'vistas' => 80000,
            'me_gusta' => 5000,
            'retencion_porcentaje' => 41.7,
            'guardados' => 300,
            'compartidos' => 200,
            'comentarios' => 150,
            'nuevos_seguidores' => 60,
            'artista_nuevo' => false,
            'notas' => null,
        ]);

        $this->consultas->shouldReceive('get')->once()->andReturn(new Collection([$registro1, $registro2]));

        $resultado = $this->repositorio->obtenerTodas();

        $this->assertCount(2, $resultado);
        $this->assertContainsOnlyInstancesOf(Publicacion::class, $resultado);
        $this->assertEquals($this->publicacionEjemplo(), $resultado[0]);
        $this->assertEquals($this->publicacionEjemplo('2024-02-20', 'Tema Dos', 'Artista Dos', 'Rock', 'Reels', 'Foto', 30, 80000, 5000, 41.7, 300, 200, 150, 60, false, null, '18:05:00'), $resultado[1]);
    }

    private function modeloConAtributos(array $atributos): PublicacionModelo
    {
        $modelo = new PublicacionModelo;
        $modelo->setRawAttributes($atributos, true);

        return $modelo;
    }

    private function publicacionEjemplo(
        string $fecha = '2024-01-15',
        string $cancion = 'Canción Uno',
        string $artista = 'Artista Uno',
        string $genero = 'Pop',
        string $sistema = 'TikTok',
        string $formato = 'Video',
        int $duracionSegundos = 60,
        int $vistas = 150000,
        int $meGusta = 12000,
        float $retencionPorcentaje = 52.3,
        int $guardados = 800,
        int $compartidos = 500,
        int $comentarios = 300,
        int $nuevosSeguidores = 120,
        bool $artistaNuevo = true,
        ?string $notas = 'Notas internas',
        string $horaPublicacion = '12:30:00',
    ): Publicacion {
        return new Publicacion(
            usuarioId: 1,
            fecha: $fecha,
            horaPublicacion: $horaPublicacion,
            cancion: $cancion,
            artista: $artista,
            genero: $genero,
            sistema: $sistema,
            formato: $formato,
            duracionSegundos: $duracionSegundos,
            vistas: $vistas,
            meGusta: $meGusta,
            retencionPorcentaje: $retencionPorcentaje,
            guardados: $guardados,
            compartidos: $compartidos,
            comentarios: $comentarios,
            nuevosSeguidores: $nuevosSeguidores,
            artistaNuevo: $artistaNuevo,
            notas: $notas,
        );
    }
}
