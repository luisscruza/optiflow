<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCurrencyRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
                Rule::notIn([request()->user()->workspace->default_currency_id ?? null]),
            ],
            'rate' => [
                'required',
                'numeric',
                'min:0.0001',
                'max:999999.9999',
            ],
            'date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency_id.required' => 'Debe seleccionar una moneda.',
            'currency_id.exists' => 'La moneda seleccionada no es válida.',
            'currency_id.not_in' => 'No puede agregar tasas para la moneda predeterminada.',
            'rate.required' => 'La tasa de cambio es obligatoria.',
            'rate.numeric' => 'La tasa de cambio debe ser un número.',
            'rate.min' => 'La tasa de cambio debe ser mayor a 0.0001.',
            'rate.max' => 'La tasa de cambio no puede exceder 999,999.9999.',
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha debe ser válida.',
            'date.before_or_equal' => 'La fecha no puede ser futura.',
            'date.unique' => 'Ya existe una tasa para esta moneda en la fecha seleccionada.',
        ];
    }
}
