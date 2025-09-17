<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'size:3', 'unique:currencies,code'],
            'symbol' => ['required', 'string', 'max:10'],
            'initial_rate' => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la moneda es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'code.required' => 'El código de la moneda es obligatorio.',
            'code.size' => 'El código debe tener exactamente 3 caracteres.',
            'code.unique' => 'Ya existe una moneda con este código.',
            'symbol.required' => 'El símbolo de la moneda es obligatorio.',
            'symbol.max' => 'El símbolo no puede exceder 10 caracteres.',
            'initial_rate.required' => 'La tasa inicial es obligatoria.',
            'initial_rate.numeric' => 'La tasa inicial debe ser un número.',
            'initial_rate.min' => 'La tasa inicial debe ser mayor a 0.0001.',
        ];
    }
}
