<?php

namespace Tests\Unit\Infrastructure\Import;

use App\Domain\Publicacion\Publicacion;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoConMacros;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoDemasiadoGrande;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoInvalido;
use App\Infrastructure\Import\Excepciones\ExcepcionDemasiadasFilas;
use App\Infrastructure\Import\LectorExcelPublicaciones;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as EscritorXlsx;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class LectorExcelPublicacionesTest extends TestCase
{
    /** @var string Directorio temporal para las pruebas. */
    private string $directorioTemporal = '';

    protected function setUp(): void
    {
        $this->directorioTemporal = sys_get_temp_dir().'/lector_excel_'.bin2hex(random_bytes(6));
        mkdir($this->directorioTemporal);
    }

    protected function tearDown(): void
    {
        if ($this->directorioTemporal !== '' && is_dir($this->directorioTemporal)) {
            $this->eliminarDirectorio($this->directorioTemporal);
        }
    }

    public function test_lee_un_archivo_valido_y_devuelve_publicaciones(): void
    {
        $ruta = $this->crearArchivoXlsx([
            $this->filaDeEncabezado(),
            ['2024-01-15', '12:30:00', 'Canción Uno', 'Artista Uno', 'Pop', 'TikTok', 'Video', 60, 150000, 12000, 52.3, 800, 500, 300, 120, 'Sí', null],
            ['2024-02-20', '18:05:00', 'Tema Dos', 'Artista Dos', 'Rock', 'Reels', 'Foto', 30, 80000, 5000, 41.7, 300, 200, 150, 60, 'No', 'Notas varias'],
        ]);

        $publicaciones = (new LectorExcelPublicaciones)->leer($ruta, 1);

        $this->assertCount(2, $publicaciones);
        $this->assertContainsOnlyInstancesOf(Publicacion::class, $publicaciones);

        $primera = $publicaciones[0];
        $this->assertSame(1, $primera->usuarioId);
        $this->assertSame('2024-01-15', $primera->fecha);
        $this->assertSame('12:30:00', $primera->horaPublicacion);
        $this->assertSame('Canción Uno', $primera->cancion);
        $this->assertSame('Artista Uno', $primera->artista);
        $this->assertSame('Pop', $primera->genero);
        $this->assertSame('TikTok', $primera->sistema);
        $this->assertSame('Video', $primera->formato);
        $this->assertSame(60, $primera->duracionSegundos);
        $this->assertSame(150000, $primera->vistas);
        $this->assertSame(12000, $primera->meGusta);
        $this->assertSame(52.3, $primera->retencionPorcentaje);
        $this->assertSame(800, $primera->guardados);
        $this->assertSame(500, $primera->compartidos);
        $this->assertSame(300, $primera->comentarios);
        $this->assertSame(120, $primera->nuevosSeguidores);
        $this->assertTrue($primera->artistaNuevo);
        $this->assertNull($primera->notas);

        $segunda = $publicaciones[1];
        $this->assertSame(1, $segunda->usuarioId);
        $this->assertSame('Tema Dos', $segunda->cancion);
        $this->assertFalse($segunda->artistaNuevo);
        $this->assertSame('Notas varias', $segunda->notas);
    }

    public function test_rechaza_archivos_de_mas_de_5_mb(): void
    {
        $ruta = $this->crearArchivoXlsx([$this->filaDeEncabezado()]);

        $gestor = fopen($ruta, 'ab');
        $aumentar = 5 * 1024 * 1024 + 1 - filesize($ruta);
        fwrite($gestor, str_repeat("\0", $aumentar));
        fclose($gestor);

        $this->expectException(ExcepcionArchivoDemasiadoGrande::class);
        $this->expectExceptionMessage('tamaño máximo');

        (new LectorExcelPublicaciones)->leer($ruta, 1);
    }

    public function test_rechaza_archivos_con_mas_de_2000_filas(): void
    {
        $filas = [$this->filaDeEncabezado()];
        for ($indice = 1; $indice <= 2001; $indice++) {
            $filas[] = ['2024-01-01', '12:00:00', "Canción {$indice}", "Artista {$indice}", 'Pop', 'TikTok', 'Video', 60, 1000, 100, 50.0, 10, 20, 30, 5, true, null];
        }

        $ruta = $this->crearArchivoXlsx($filas);

        $this->expectException(ExcepcionDemasiadasFilas::class);
        $this->expectExceptionMessage('límite de 2000 filas');

        (new LectorExcelPublicaciones)->leer($ruta, 1);
    }

    public function test_sanitiza_payloads_de_inyeccion_de_formulas(): void
    {
        $columnasDeTexto = [2, 3, 4, 6, 16]; // cancion, artista, genero, formato, notas
        $ruta = $this->crearArchivoXlsx([
            $this->filaDeEncabezado(),
            ['2024-01-01', '12:00:00', '=1+1', '+cmd|"/c calc"!A0', '@SUM(1,2)', 'TikTok', '-123', 60, 100, 10, 5.0, 1, 2, 3, 4, 'No', '=HYPERLINK("http://mal.com","clic")'],
        ], $columnasDeTexto);

        $publicaciones = (new LectorExcelPublicaciones)->leer($ruta, 1);

        $this->assertCount(1, $publicaciones);
        $publicacion = $publicaciones[0];
        $this->assertSame(1, $publicacion->usuarioId);
        $this->assertSame("'=1+1", $publicacion->cancion);
        $this->assertSame("'+cmd|\"/c calc\"!A0", $publicacion->artista);
        $this->assertSame("'@SUM(1,2)", $publicacion->genero);
        $this->assertSame("'-123", $publicacion->formato);
        $this->assertSame("'=HYPERLINK(\"http://mal.com\",\"clic\")", $publicacion->notas);
    }

    public function test_rechaza_archivos_con_macros_xlsm(): void
    {
        $ruta = $this->crearArchivoXlsx([$this->filaDeEncabezado()], [], 'xlsm');

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($ruta), 'No se pudo abrir el archivo generado.');
        $this->assertTrue($zip->addFromString('xl/vbaProject.bin', 'contenido de macros falso'));
        $zip->close();

        $this->expectException(ExcepcionArchivoConMacros::class);
        $this->expectExceptionMessage('macros');

        (new LectorExcelPublicaciones)->leer($ruta, 1);
    }

    public function test_interpreta_fechas_y_horas_reales_de_excel(): void
    {
        $ruta = $this->directorioTemporal.'/fechas_reales.xlsx';
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();

        foreach ($this->filaDeEncabezado() as $indice => $valor) {
            $hoja->setCellValue(Coordinate::stringFromColumnIndex($indice + 1).'1', $valor);
        }

        $hoja->setCellValue('A2', Date::PHPToExcel(new \DateTimeImmutable('2024-05-06')));
        $hoja->getStyle('A2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $hoja->setCellValue('B2', 0.29583333333333337); // 07:06:00 aprox
        $hoja->getStyle('B2')->getNumberFormat()->setFormatCode('hh:mm:ss');
        $hoja->setCellValue('C2', 'Tema con fechas reales');
        $hoja->setCellValue('H2', 30);
        $hoja->setCellValue('O2', 5);
        $hoja->setCellValue('P2', true);
        (new EscritorXlsx($libro))->save($ruta);
        $libro->disconnectWorksheets();

        $publicaciones = (new LectorExcelPublicaciones)->leer($ruta, 1);

        $this->assertCount(1, $publicaciones);
        $this->assertSame(1, $publicaciones[0]->usuarioId);
        $this->assertSame('2024-05-06', $publicaciones[0]->fecha);
        $this->assertSame('07:06:00', $publicaciones[0]->horaPublicacion);
        $this->assertSame('Tema con fechas reales', $publicaciones[0]->cancion);
        $this->assertTrue($publicaciones[0]->artistaNuevo);
    }

    public function test_nunca_evalua_formulas_y_usa_el_valor_cacheado(): void
    {
        $ruta = $this->directorioTemporal.'/formulas.xlsx';
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();

        foreach ($this->filaDeEncabezado() as $indice => $valor) {
            $hoja->setCellValue(Coordinate::stringFromColumnIndex($indice + 1).'1', $valor);
        }

        $hoja->setCellValue('C2', '=CONCATENATE("a","b")');
        $hoja->setCellValue('I2', '=100+50');
        (new EscritorXlsx($libro))->save($ruta);
        $libro->disconnectWorksheets();

        $publicaciones = (new LectorExcelPublicaciones)->leer($ruta, 1);

        $this->assertCount(1, $publicaciones);
        $this->assertSame(1, $publicaciones[0]->usuarioId);
        $this->assertSame('ab', $publicaciones[0]->cancion);
        $this->assertSame(150, $publicaciones[0]->vistas);
    }

    public function test_formula_en_fecha_se_lee_desde_el_valor_cacheado(): void
    {
        $ruta = $this->directorioTemporal.'/fecha_formula.xlsx';
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();

        foreach ($this->filaDeEncabezado() as $indice => $valor) {
            $hoja->setCellValue(Coordinate::stringFromColumnIndex($indice + 1).'1', $valor);
        }

        $hoja->setCellValue('A2', '=DATE(2024,5,6)');
        $hoja->getStyle('A2')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $hoja->setCellValue('C2', 'Tema con fórmula de fecha');
        (new EscritorXlsx($libro))->save($ruta);
        $libro->disconnectWorksheets();

        $publicaciones = (new LectorExcelPublicaciones)->leer($ruta, 1);

        $this->assertCount(1, $publicaciones);
        $this->assertSame(1, $publicaciones[0]->usuarioId);
        $this->assertSame('2024-05-06', $publicaciones[0]->fecha);
        $this->assertSame('Tema con fórmula de fecha', $publicaciones[0]->cancion);
    }

    public function test_rechaza_contenido_no_excel_aunque_la_extension_sea_xlsx(): void
    {
        $ruta = $this->directorioTemporal.'/falso.xlsx';
        file_put_contents($ruta, 'contenido que no es un archivo Excel');

        $this->expectException(ExcepcionArchivoInvalido::class);
        $this->expectExceptionMessage('firma de bytes');

        (new LectorExcelPublicaciones)->leer($ruta, 1);
    }

    /**
     * @return list<string>
     */
    private function filaDeEncabezado(): array
    {
        return [
            'fecha', 'horaPublicacion', 'cancion', 'artista', 'genero', 'sistema',
            'formato', 'duracionSegundos', 'vistas', 'meGusta', 'retencionPorcentaje',
            'guardados', 'compartidos', 'comentarios', 'nuevosSeguidores',
            'artistaNuevo', 'notas',
        ];
    }

    /**
     * Crea un archivo .xlsx real usando el escritor de PhpSpreadsheet.
     *
     * @param  list<list<string|int|float|bool|null>>  $filas
     * @param  list<int>  $columnasForzadasAString  columnas (índice 0) que se escriben como texto explícito
     */
    private function crearArchivoXlsx(array $filas, array $columnasForzadasAString = [], string $extension = 'xlsx'): string
    {
        $ruta = $this->directorioTemporal.'/'.bin2hex(random_bytes(6)).".{$extension}";

        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();

        foreach ($filas as $indiceFila => $fila) {
            $numeroFila = $indiceFila + 1;
            foreach ($fila as $indiceColumna => $valor) {
                if ($valor === null) {
                    continue;
                }
                $referencia = Coordinate::stringFromColumnIndex($indiceColumna + 1).$numeroFila;
                if (in_array($indiceColumna, $columnasForzadasAString, true)) {
                    $hoja->setCellValueExplicit($referencia, (string) $valor, DataType::TYPE_STRING);
                } else {
                    $hoja->setCellValue($referencia, $valor);
                }
            }
        }

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
