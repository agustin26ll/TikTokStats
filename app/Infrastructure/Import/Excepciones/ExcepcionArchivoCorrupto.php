<?php

namespace App\Infrastructure\Import\Excepciones;

/**
 * El archivo tiene firma ZIP válida pero no pudo ser interpretado como un
 * libro de Excel (.xlsx) por el lector.
 */
class ExcepcionArchivoCorrupto extends ExcepcionImportacion {}
