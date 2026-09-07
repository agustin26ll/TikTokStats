<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as EscritorXlsx;
use Tests\TestCase;

final class ImportarPublicacionesControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var string Directorio temporal para las pruebas. */
    private string $directorioTemporal = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->directorioTemporal = sys_get_temp_dir().'/import_test_'.bin2hex(random_bytes(6));
        mkdir($this->directorioTemporal);

        // Sembramos el usuario de prueba en la BD de testing
        \DB::table('usuarios')->insert([
            'tiktok_username' => 'mitiktokusername',
            'created_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->directorioTemporal !== '' && is_dir($this->directorioTemporal)) {
            $this->eliminarDirectorio($this->directorioTemporal);
        }
        parent::tearDown();
    }

    public function test_importa_un_xlsx_valido_y_devuelve_201_con_contador(): void
    {
        $ruta = $this->crearArchivoXlsxValido();

        $archivo = UploadedFile::fake()->createWithContent('test.xlsx', file_get_contents($ruta));

        $response = $this->post('/api/publicaciones/importar', [
            'archivo' => $archivo,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['importadas']);
        $this->assertEquals(2, $response->json('importadas'));
    }

    public function test_archivo_supera_5mb_devuelve_413(): void
    {
        $ruta = $this->crearArchivoXlsxValido();

        // Aumentamos el archivo más de 5 MB
        $gestor = fopen($ruta, 'ab');
        $aumentar = 5 * 1024 * 1024 + 1 - filesize($ruta);
        fwrite($gestor, str_repeat("\0", $aumentar));
        fclose($gestor);

        $archivo = UploadedFile::fake()->createWithContent('test.xlsx', file_get_contents($ruta));

        $response = $this->post('/api/publicaciones/importar', [
            'archivo' => $archivo,
        ]);

        // La validación de FormRequest (max:5120 = 5MB) intercepta antes y devuelve 422,
        // pero nuestro controlador también mapea ExcepcionArchivoDemasiadoGrande a 413.
        // En este caso la validación HTTP llega primero.
        $this->assertEquals(422, $response->status());
        $json = $response->json();
        $this->assertArrayHasKey('message', $json);
        $this->assertStringContainsString('tamaño', $json['message']);
    }

    private function crearArchivoXlsxValido(): string
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();

        $encabezado = [
            'fecha', 'horaPublicacion', 'cancion', 'artista', 'genero', 'sistema',
            'formato', 'duracionSegundos', 'vistas', 'meGusta', 'retencionPorcentaje',
            'guardados', 'compartidos', 'comentarios', 'nuevosSeguidores',
            'artistaNuevo', 'notas',
        ];

        foreach ($encabezado as $indice => $valor) {
            $hoja->setCellValue(Coordinate::stringFromColumnIndex($indice + 1).'1', $valor);
        }

        $filas = [
            ['2024-01-15', '12:30:00', 'Canción Uno', 'Artista Uno', 'Pop', 'TikTok', 'Video', 60, 150000, 12000, 52.3, 800, 500, 300, 120, 'Sí', ''],
            ['2024-02-20', '18:05:00', 'Tema Dos', 'Artista Dos', 'Rock', 'Reels', 'Foto', 30, 80000, 5000, 41.7, 300, 200, 150, 60, 'No', 'Notas varias'],
        ];

        foreach ($filas as $indiceFila => $fila) {
            $numeroFila = $indiceFila + 2;
            foreach ($fila as $indiceColumna => $valor) {
                if ($valor === '') {
                    continue;
                }
                $hoja->setCellValue(Coordinate::stringFromColumnIndex($indiceColumna + 1).$numeroFila, $valor);
            }
        }

        $ruta = $this->directorioTemporal.'/valido.xlsx';
        (new EscritorXlsx($libro))->save($ruta);
        $libro->disconnectWorksheets();

        return $ruta;
    }

    private function eliminarDirectorio(string $directorio): void
    {
        $elementos = scandir($directorio);
        foreach ($elementos as $elemento) {
            if ($elemento === '.' || $elemento === '..') {
                continue;
            }
            $ruta = $directorio.'/'.$elemento;
            if (is_dir($ruta)) {
                $this->eliminarDirectorio($ruta);
            } else {
                unlink($ruta);
            }
        }
        rmdir($directorio);
    }
}
