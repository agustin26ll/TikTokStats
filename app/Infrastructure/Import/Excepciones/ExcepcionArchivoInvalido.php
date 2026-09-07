<?php

namespace App\Infrastructure\Import\Excepciones;

/**
 * El archivo no existe, no es legible o su contenido no corresponde a un
 * Excel moderno (.xlsx). Se valida por firma de bytes, no por extensión.
 */
class ExcepcionArchivoInvalido extends ExcepcionImportacion {}
