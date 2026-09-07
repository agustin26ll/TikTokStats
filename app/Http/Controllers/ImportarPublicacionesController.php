<?php

namespace App\Http\Controllers;

use App\Application\Publicacion\ImportarPublicaciones;
use App\Domain\Publicacion\PublicacionRepositorioInterface;
use App\Http\Requests\ImportarPublicacionesRequest;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoConMacros;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoCorrupto;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoDemasiadoGrande;
use App\Infrastructure\Import\Excepciones\ExcepcionArchivoInvalido;
use App\Infrastructure\Import\Excepciones\ExcepcionDemasiadasFilas;
use App\Infrastructure\Import\LectorExcelPublicaciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

class ImportarPublicacionesController extends Controller
{
    public function __construct(
        private readonly ImportarPublicaciones $casoDeUso,
        private readonly LectorExcelPublicaciones $lector,
        private readonly PublicacionRepositorioInterface $repositorio,
    ) {}

    /**
     * POST /api/publicaciones/importar
     *
     * Importa un archivo Excel de estadísticas de TikTok.
     *
     * @return JsonResponse{importadas: int} 201
     * @return JsonResponse{error: string} 422
     */
    public function importar(ImportarPublicacionesRequest $request): JsonResponse
    {
        /** @var UploadedFile $archivo */
        $archivo = $request->file('archivo');

        // Obtenemos el usuario de prueba sembrado en la base de datos.
        // En el futuro esto vendrá del usuario autenticado (tarea separada).
        $usuario = \DB::table('usuarios')->where('tiktok_username', 'mitiktokusername')->first();

        if ($usuario === null) {
            return response()->json([
                'error' => 'Usuario de prueba no encontrado. Ejecuta el seeder.',
            ], 500);
        }

        try {
            $publicaciones = $this->lector->leer($archivo->getRealPath(), $usuario->id);
            $importadas = $this->casoDeUso->importar($publicaciones);

            return response()->json([
                'importadas' => $importadas,
            ], 201);
        } catch (Throwable $e) {
            // Mapeamos excepciones de dominio a 422 con mensaje específico.
            $codigo = match (true) {
                $e instanceof ExcepcionArchivoDemasiadoGrande => 413,
                $e instanceof ExcepcionDemasiadasFilas => 422,
                $e instanceof ExcepcionArchivoConMacros => 422,
                $e instanceof ExcepcionArchivoInvalido => 422,
                $e instanceof ExcepcionArchivoCorrupto => 422,
                default => 500,
            };

            return response()->json([
                'error' => $e->getMessage(),
            ], $codigo);
        }
    }
}
