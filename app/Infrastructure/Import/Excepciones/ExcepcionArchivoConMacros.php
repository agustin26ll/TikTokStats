<?php

namespace App\Infrastructure\Import\Excepciones;

/**
 * El archivo contiene macros (vbaproject.bin), típico de .xlsm. Las macros
 * se rechazan por motivos de seguridad aunque el archivo use extensión .xlsx.
 */
class ExcepcionArchivoConMacros extends ExcepcionImportacion {}
