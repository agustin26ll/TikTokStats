<?php

namespace App\Infrastructure\Import;

use App\Domain\Publicacion\Publicacion;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoConMacros;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoCorrupto;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoDemasiadoGrande;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoInvalido;
use App\Infrastructure\Import\Excepciones\ExcepcionDemasiadasFilas;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as ExcepcionPhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as FechaExcel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ZipArchive;

/**
 * Lector de archivos Excel (.xlsx) de estadísticas de publicaciones.
 *
 * Requisitos de seguridad (no negociables):
 *  - Validación por firma de bytes, nunca por extensión.
 *  - Rechazo de archivos con macros (.xlsm) por contenido, no por extensión.
 *  - Rechazo de archivos de más de 5 MB o de más de 2000 filas.
 *  - Lectura en modo de solo datos (data_only): nunca se evalúan fórmulas,
 *    solo se leen los valores cacheados almacenados en el libro.
 *  - Sanitización de todas las celdas de texto (neutraliza inyección de
 *    fórmulas y elimina caracteres de control).
 */
final class LectorExcelPublicaciones
{
    /** Tamaño máximo permitido: 5 MB. */
    private const TAMANO_MAXIMO_BYTES = 5 * 1024 * 1024;

    /** Número máximo de filas de datos permitidas. */
    private const FILAS_MAXIMAS = 2000;

    /** Firmas ZIP válidas para un archivo .xlsx. */
    private const FIRMAS_ZIP = [
        "PK\x03\x04", // archivo ZIP estándar
        "PK\x05\x06", // ZIP vacío (solo registro fin central)
        "PK\x07\x08", // ZIP fragmentado/span
    ];

    /** Firma de los archivos .xls clásicos (OLE2), rechazados. */
    private const FIRMA_XLS_LEGACY = "\xD0\xCF\x11\xE0";

    /** Caracteres que disparan inyección de fórmulas en hojas de cálculo. */
    private const CARACTERES_FORMULA = ['=', '+', '-', '@'];

    /** Mapa columna => campo del dominio (A..Q). */
    private const COLUMNAS = [
        'fecha' => 'A',
        'horaPublicacion' => 'B',
        'cancion' => 'C',
        'artista' => 'D',
        'genero' => 'E',
        'sistema' => 'F',
        'formato' => 'G',
        'duracionSegundos' => 'H',
        'vistas' => 'I',
        'meGusta' => 'J',
        'retencionPorcentaje' => 'K',
        'guardados' => 'L',
        'compartidos' => 'M',
        'comentarios' => 'N',
        'nuevosSeguidores' => 'O',
        'artistaNuevo' => 'P',
        'notas' => 'Q',
    ];

    /** Columnas que deben contener valores numéricos (usan la fila 1 para detectar encabezado). */
    private const COLUMNAS_NUMERICAS = ['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];

    /**
     * Lee un archivo Excel y devuelve las publicaciones importadas.
     *
     * @param  int  $usuarioId  Identificador del usuario propietario de las publicaciones.
     * @return list<Publicacion>
     *
     * @throws ExcepcionArchivoInvalido
     * @throws ExcepcionArchivoDemasiadoGrande
     * @throws ExcepcionArchivoConMacros
     * @throws ExcepcionDemasiadasFilas
     * @throws ExcepcionArchivoCorrupto
     */
    public function leer(string $rutaArchivo, int $usuarioId): array
    {
        $this->validarExistencia($rutaArchivo);
        $this->validarTamano($rutaArchivo);
        $this->validarFirma($rutaArchivo);
        $this->rechazarMacros($rutaArchivo);

        $lector = new Xlsx;
        $lector->setReadEmptyCells(false);

        // Nota sobre "solo datos": NO se usa setReadDataOnly(true) porque en
        // PhpSpreadsheet 5.x ese modo también descarta los formatos de fecha
        // (dejaría las fechas reales como número de serie) y no evita que
        // getValue() exponga la fórmula. La seguridad se garantiza en el
        // código: cada celda se lee a través de valorAlmacenado(), que nunca
        // devuelve una fórmula y jamás invoca los métodos que recalculan
        // (getCalculatedValue, getFormattedValue sobre celdas con fórmula).

        try {
            $libro = $lector->load($rutaArchivo);
        } catch (ExcepcionPhpSpreadsheet $e) {
            throw new ExcepcionArchivoCorrupto(
                'El archivo no pudo interpretarse como un libro de Excel .xlsx.',
                0,
                $e,
            );
        }

        try {
            return $this->construirPublicaciones($libro, $usuarioId);
        } finally {
            $libro->disconnectWorksheets();
        }
    }

    private function validarExistencia(string $rutaArchivo): void
    {
        if (! is_file($rutaArchivo) || ! is_readable($rutaArchivo)) {
            throw new ExcepcionArchivoInvalido(
                "El archivo no existe o no es legible: {$rutaArchivo}",
            );
        }
    }

    private function validarTamano(string $rutaArchivo): void
    {
        $tamano = @filesize($rutaArchivo);
        if ($tamano === false) {
            throw new ExcepcionArchivoInvalido('No se pudo conocer el tamaño del archivo.');
        }
        if ($tamano > self::TAMANO_MAXIMO_BYTES) {
            throw new ExcepcionArchivoDemasiadoGrande(sprintf(
                'El archivo supera el tamaño máximo permitido de %d MB.',
                self::TAMANO_MAXIMO_BYTES / 1024 / 1024,
            ));
        }
    }

    /**
     * Valida la firma de bytes del archivo. Nunca se usa la extensión.
     */
    private function validarFirma(string $rutaArchivo): void
    {
        $gestor = @fopen($rutaArchivo, 'rb');
        if ($gestor === false) {
            throw new ExcepcionArchivoInvalido('No se pudo abrir el archivo para validar su contenido.');
        }
        $bytes = (string) fread($gestor, 4);
        fclose($gestor);

        if (in_array($bytes, self::FIRMAS_ZIP, true)) {
            return;
        }

        if ($bytes === self::FIRMA_XLS_LEGACY) {
            throw new ExcepcionArchivoInvalido(
                'Los archivos .xls clásicos no están soportados; exporta la hoja en formato .xlsx.',
            );
        }

        throw new ExcepcionArchivoInvalido(
            'El contenido del archivo no corresponde a un Excel .xlsx (firma de bytes inválida).',
        );
    }

    /**
     * Rechaza archivos con macros. Un .xlsm y un .xlsx comparten la misma
     * firma ZIP; la diferencia está en la presencia de vbaproject.bin dentro
     * del paquete, por lo que se inspecciona el contenido del ZIP.
     */
    private function rechazarMacros(string $rutaArchivo): void
    {
        $zip = new ZipArchive;
        if ($zip->open($rutaArchivo) !== true) {
            throw new ExcepcionArchivoCorrupto(
                'No se pudo abrir el contenido ZIP del archivo Excel.',
            );
        }

        try {
            for ($indice = 0; $indice < $zip->numFiles; $indice++) {
                $nombre = (string) $zip->getNameIndex($indice);
                if (str_ends_with(strtolower($nombre), 'vbaproject.bin')) {
                    throw new ExcepcionArchivoConMacros(
                        'El archivo contiene macros (vbaproject.bin) y está rechazado por seguridad.',
                    );
                }
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @return list<Publicacion>
     */
    private function construirPublicaciones(Spreadsheet $libro, int $usuarioId): array
    {
        $hoja = $libro->getActiveSheet();

        if ($hoja->getHighestDataRow() > self::FILAS_MAXIMAS) {
            throw new ExcepcionDemasiadasFilas(sprintf(
                'El archivo supera el límite de %d filas de datos.',
                self::FILAS_MAXIMAS,
            ));
        }

        $publicaciones = [];
        $numeroFila = 0;

        foreach ($hoja->getRowIterator() as $fila) {
            $numeroFila++;

            if ($numeroFila > self::FILAS_MAXIMAS) {
                throw new ExcepcionDemasiadasFilas(sprintf(
                    'El archivo supera el límite de %d filas de datos.',
                    self::FILAS_MAXIMAS,
                ));
            }

            if ($numeroFila === 1 && $this->esFilaEncabezado($hoja)) {
                continue;
            }

            if (! $this->filaConDatos($fila)) {
                continue;
            }

            $publicaciones[] = $this->construirPublicacion($hoja, $numeroFila, $usuarioId);
        }

        return $publicaciones;
    }

    /**
     * Detecta si la primera fila es un encabezado de columnas: se comprueba
     * que, en las columnas numéricas, no haya ningún valor numérico real.
     */
    private function esFilaEncabezado(Worksheet $hoja): bool
    {
        $encontroTextoNoNumerico = false;

        foreach (self::COLUMNAS_NUMERICAS as $columna) {
            $valor = $this->valorAlmacenado($hoja->getCell($columna.'1'));
            if ($valor === null || $valor === '') {
                continue;
            }
            if (is_numeric($valor)) {
                return false;
            }
            $encontroTextoNoNumerico = true;
        }

        return $encontroTextoNoNumerico;
    }

    private function filaConDatos(Row $fila): bool
    {
        $iterador = $fila->getCellIterator();
        $iterador->setIterateOnlyExistingCells(true);

        foreach ($iterador as $celda) {
            $valor = $celda->getValue();
            if ($valor !== null && $valor !== '') {
                return true;
            }
        }

        return false;
    }

    private function construirPublicacion(Worksheet $hoja, int $numeroFila, int $usuarioId): Publicacion
    {
        return new Publicacion(
            usuarioId: $usuarioId,
            fecha: $this->leerFecha($hoja, $numeroFila),
            horaPublicacion: $this->leerHora($hoja, $numeroFila),
            cancion: $this->leerTexto($hoja, $numeroFila, 'cancion'),
            artista: $this->leerTexto($hoja, $numeroFila, 'artista'),
            genero: $this->leerTexto($hoja, $numeroFila, 'genero'),
            sistema: $this->leerTexto($hoja, $numeroFila, 'sistema'),
            formato: $this->leerTexto($hoja, $numeroFila, 'formato'),
            duracionSegundos: $this->leerEntero($hoja, $numeroFila, 'duracionSegundos'),
            vistas: $this->leerEntero($hoja, $numeroFila, 'vistas'),
            meGusta: $this->leerEntero($hoja, $numeroFila, 'meGusta'),
            retencionPorcentaje: $this->leerFlotante($hoja, $numeroFila, 'retencionPorcentaje'),
            guardados: $this->leerEntero($hoja, $numeroFila, 'guardados'),
            compartidos: $this->leerEntero($hoja, $numeroFila, 'compartidos'),
            comentarios: $this->leerEntero($hoja, $numeroFila, 'comentarios'),
            nuevosSeguidores: $this->leerEntero($hoja, $numeroFila, 'nuevosSeguidores'),
            artistaNuevo: $this->leerBooleano($hoja, $numeroFila, 'artistaNuevo'),
            notas: $this->leerNotas($hoja, $numeroFila),
        );
    }

    /**
     * Fecha y hora se leen con el formato de la celda para adaptarse tanto a
     * celdas de texto como a celdas de fecha/hora reales de Excel.
     */
    private function leerFecha(Worksheet $hoja, int $numeroFila): string
    {
        return $this->leerValorFecha($hoja, $numeroFila, 'fecha', 'Y-m-d');
    }

    private function leerHora(Worksheet $hoja, int $numeroFila): string
    {
        return $this->leerValorFecha($hoja, $numeroFila, 'horaPublicacion', 'H:i:s');
    }

    private function leerValorFecha(Worksheet $hoja, int $numeroFila, string $campo, string $formatoDeseado): string
    {
        $celda = $hoja->getCell(self::COLUMNAS[$campo].$numeroFila);

        if ($celda->getDataType() !== DataType::TYPE_FORMULA) {
            // Celda sin fórmula: getFormattedValue() no recalcula nada.
            return $this->sanitizarTexto(trim((string) $celda->getFormattedValue()));
        }

        // Celda con fórmula: jamás se evalúa; solo se usa el valor cacheado.
        return $this->sanitizarTexto(
            $this->formatearFechaDesdeExcel($celda->getOldCalculatedValue(), $formatoDeseado),
        );
    }

    /**
     * Convierte un valor cacheado de fecha/hora a texto. Si es un número de
     * serie de Excel se interpreta como fecha; en otro caso se devuelve
     * tal cual.
     */
    private function formatearFechaDesdeExcel(mixed $valor, string $formatoDeseado): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        if (is_numeric($valor) && is_finite((float) $valor) && (float) $valor > 0 && (float) $valor < 2958466) {
            return FechaExcel::excelToDateTimeObject((float) $valor)->format($formatoDeseado);
        }

        return trim((string) $valor);
    }

    private function leerTexto(Worksheet $hoja, int $numeroFila, string $campo): string
    {
        $valor = $this->valorAlmacenado($hoja->getCell(self::COLUMNAS[$campo].$numeroFila));

        if ($valor === null || $valor === '') {
            return '';
        }

        return $this->sanitizarTexto((string) $valor);
    }

    private function leerEntero(Worksheet $hoja, int $numeroFila, string $campo): int
    {
        return $this->convertirEntero($this->valorAlmacenado($hoja->getCell(self::COLUMNAS[$campo].$numeroFila)));
    }

    private function leerFlotante(Worksheet $hoja, int $numeroFila, string $campo): float
    {
        return $this->convertirFlotante($this->valorAlmacenado($hoja->getCell(self::COLUMNAS[$campo].$numeroFila)));
    }

    private function leerBooleano(Worksheet $hoja, int $numeroFila, string $campo): bool
    {
        $valor = $this->valorAlmacenado($hoja->getCell(self::COLUMNAS[$campo].$numeroFila));

        if (is_bool($valor)) {
            return $valor;
        }
        if (is_int($valor) || is_float($valor)) {
            return $valor != 0;
        }
        if ($valor === null) {
            return false;
        }

        $texto = mb_strtolower(trim((string) $valor), 'UTF-8');

        return in_array($texto, ['sí', 'si', 'yes', 'y', 'true', 'verdadero', 'x', '1'], true);
    }

    private function leerNotas(Worksheet $hoja, int $numeroFila): ?string
    {
        $valor = $this->valorAlmacenado($hoja->getCell(self::COLUMNAS['notas'].$numeroFila));

        if ($valor === null || $valor === '') {
            return null;
        }

        $texto = $this->sanitizarTexto((string) $valor);

        return $texto === '' ? null : $texto;
    }

    /**
     * Devuelve el valor de una celda sin exponer nunca la fórmula: si la
     * celda contiene una fórmula se usa el resultado cacheado; de lo
     * contrario, el valor almacenado. No se invoca getCalculatedValue().
     */
    private function valorAlmacenado(Cell $celda): mixed
    {
        if ($celda->getDataType() === DataType::TYPE_FORMULA) {
            return $celda->getOldCalculatedValue();
        }

        return $celda->getValue();
    }

    private function convertirEntero(mixed $valor): int
    {
        if ($valor === null || $valor === '') {
            return 0;
        }
        if (is_int($valor)) {
            return $valor;
        }
        if (is_float($valor)) {
            return (int) round($valor);
        }
        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }
        if (is_numeric($valor)) {
            return (int) round((float) $valor);
        }

        // Texto con separadores de miles (p. ej. "150 000" o "150,000").
        $texto = str_replace([' ', ','], '', (string) $valor);

        return is_numeric($texto) ? (int) round((float) $texto) : 0;
    }

    private function convertirFlotante(mixed $valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }
        if (is_float($valor)) {
            return $valor;
        }
        if (is_int($valor)) {
            return (float) $valor;
        }
        if (is_bool($valor)) {
            return $valor ? 1.0 : 0.0;
        }
        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $texto = str_replace(',', '', (string) $valor);

        return is_numeric($texto) ? (float) $texto : 0.0;
    }

    /**
     * Sanitiza una celda de texto:
     *  - ajusta espacios al inicio y al final,
     *  - elimina caracteres de control y bytes nulos,
     *  - neutraliza la inyección de fórmulas anteponiendo una comilla simple
     *    a los valores que empiecen por =, +, - o @.
     */
    private function sanitizarTexto(string $valor): string
    {
        $valor = trim($valor);
        $valor = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valor);

        if ($valor !== '' && in_array($valor[0], self::CARACTERES_FORMULA, true)) {
            $valor = "'".$valor;
        }

        return $valor;
    }
}
