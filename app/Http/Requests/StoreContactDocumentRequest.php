<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

final class StoreContactDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*' => ['required', 'file', File::types(['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])->max(3 * 1024)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documents.required' => 'Debes seleccionar al menos un documento.',
            'documents.array' => 'Los documentos enviados no son válidos.',
            'documents.min' => 'Debes seleccionar al menos un documento.',
            'documents.max' => 'No puedes subir más de 10 documentos a la vez.',
            'documents.*.required' => 'Cada documento es obligatorio.',
            'documents.*.file' => 'Cada documento debe ser un archivo válido.',
            'documents.*.max' => 'Cada documento no puede exceder 3MB.',
            'documents.*.types' => 'Los documentos deben ser PDF o imágenes.',
        ];
    }
}
