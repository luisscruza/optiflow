<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateEasyFactuSettingsRequest extends FormRequest
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
            'environment' => ['required', 'string', Rule::in(['TesteCF', 'CerteCF', 'eCF'])],
            'api_key_testecf' => ['nullable', 'string', 'max:255'],
            'api_key_certecf' => ['nullable', 'string', 'max:255'],
            'api_key_ecf' => ['nullable', 'string', 'max:255'],
            'base_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'environment.required' => 'El entorno es obligatorio.',
            'environment.in' => 'El entorno debe ser TesteCF, CerteCF o eCF.',
            'base_url.url' => 'La URL base debe ser una URL válida.',
        ];
    }
}
