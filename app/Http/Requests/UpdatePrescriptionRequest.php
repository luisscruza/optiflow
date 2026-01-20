<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePrescriptionRequest extends FormRequest
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
        $rules = [
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'optometrist_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'motivos_consulta' => ['nullable', 'array'],
            'motivos_consulta.*' => ['integer', 'exists:mastertable_items,id'],
            'estado_salud_actual' => ['nullable', 'array'],
            'estado_salud_actual.*' => ['integer', 'exists:mastertable_items,id'],
            'historia_ocular_familiar' => ['nullable', 'array'],
            'historia_ocular_familiar.*' => ['integer', 'exists:mastertable_items,id'],
        ];

        foreach ($this->except(array_keys($rules)) as $key => $value) {
            $rules[$key] = ['nullable'];
        }

        return $rules;
    }
}
