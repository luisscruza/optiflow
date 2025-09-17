<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateDocumentSubtypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:3|unique:document_subtypes,prefix',
            'start_number' => 'required|integer|min:1',
            'end_number' => 'nullable|integer|gt:start_number',
            'valid_until_date' => 'nullable|date|after:today',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'prefix.required' => 'El prefijo es obligatorio.',
            'prefix.max' => 'El prefijo no puede tener más de 3 caracteres.',
            'prefix.unique' => 'Este prefijo ya está en uso.',
            'start_number.required' => 'El número inicial es obligatorio.',
            'start_number.min' => 'El número inicial debe ser mayor a 0.',
            'end_number.gt' => 'El número final debe ser mayor al número inicial.',
            'valid_until_date.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'prefix' => 'prefijo',
            'start_number' => 'número inicial',
            'end_number' => 'número final',
            'valid_until_date' => 'fecha de vencimiento',
            'is_default' => 'preferida',
        ];
    }
}
