<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;

final class CreateContactImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->current_workspace_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'nullable',
                'required_without:files',
                File::types(['csv', 'txt'])->max(10 * 1024)->min(1),
            ],
            'files' => ['nullable', 'required_without:file', 'array', 'min:1'],
            'files.*' => [
                'required',
                File::types(['csv', 'txt'])->max(10 * 1024)->min(1),
            ],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Debe seleccionar un archivo CSV.',
            'files.required_without' => 'Debe seleccionar al menos un archivo CSV.',
            'files.min' => 'Debe seleccionar al menos un archivo CSV.',
            'files.*.required' => 'Cada archivo es obligatorio.',
            'file.max' => 'El archivo no puede exceder 10MB.',
            'file.min' => 'El archivo es demasiado pequeño. Cargue un CSV válido.',
            'files.*.max' => 'Cada archivo no puede exceder 10MB.',
            'files.*.min' => 'Un archivo es demasiado pequeño. Cargue CSVs válidos.',
        ];
    }
}
