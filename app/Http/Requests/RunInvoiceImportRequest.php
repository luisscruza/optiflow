<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class RunInvoiceImportRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file_path' => ['required', 'string', 'starts_with:imports/invoices/'],
            'filename' => ['nullable', 'string'],
            'original_filename' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'offset' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file_path.required' => 'Debe seleccionar un archivo antes de iniciar la importación.',
            'file_path.starts_with' => 'La ruta del archivo es inválida.',
            'limit.integer' => 'El límite debe ser un número entero.',
            'limit.min' => 'El límite debe ser mayor a 0.',
            'limit.max' => 'El límite no puede superar 10,000.',
            'offset.integer' => 'El offset debe ser un número entero.',
            'offset.min' => 'El offset no puede ser negativo.',
            'offset.max' => 'El offset no puede superar 1,000,000.',
        ];
    }
}
