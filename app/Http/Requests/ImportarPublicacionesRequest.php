<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la petición de importación de publicaciones.
 *
 * Defensa en profundidad: el lector LectorExcelPublicaciones ya hace
 * validaciones exhaustivas (firma, macros, filas, tamaño, fórmulas).
 * Aquí solo comprobamos que el archivo existe y no supera 5 MB — es
 * una barrera temprana y barata antes de llegar a la lógica de dominio.
 */
final class ImportarPublicacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'max:5120'], // 5 MB = 5120 KB
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'El archivo es obligatorio.',
            'archivo.file' => 'El campo debe ser un archivo subido.',
            'archivo.max' => 'El archivo supera el tamaño máximo de 5 MB.',
        ];
    }
}
