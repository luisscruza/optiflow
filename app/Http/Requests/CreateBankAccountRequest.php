<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BankAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::enum(BankAccountType::class)],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'initial_balance' => ['required', 'numeric'],
            'initial_balance_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la cuenta es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'type.required' => 'El tipo de cuenta es obligatorio.',
            'type.enum' => 'El tipo de cuenta seleccionado no es válido.',
            'currency_id.required' => 'La moneda es obligatoria.',
            'currency_id.exists' => 'La moneda seleccionada no existe.',
            'account_number.max' => 'El número de cuenta no puede exceder 255 caracteres.',
            'initial_balance.required' => 'El balance inicial es obligatorio.',
            'initial_balance.numeric' => 'El balance inicial debe ser un número.',
            'initial_balance_date.required' => 'La fecha del balance inicial es obligatoria.',
            'initial_balance_date.date' => 'La fecha del balance inicial debe ser una fecha válida.',
            'description.max' => 'La descripción no puede exceder 1000 caracteres.',
        ];
    }
}
