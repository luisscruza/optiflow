<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePaymentRequest extends FormRequest
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
        $payment = $this->route('payment');

        $rules = [
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];

        // For invoice payments
        if ($payment && $payment->payment_type === PaymentType::InvoicePayment) {
            $rules['amount'] = ['required', 'numeric', 'min:0.01'];
        }

        // For other income payments
        if ($payment && $payment->payment_type === PaymentType::OtherIncome) {
            $rules['contact_id'] = ['nullable', 'exists:contacts,id'];

            // Lines for other income
            $rules['lines'] = ['required', 'array', 'min:1'];
            $rules['lines.*.id'] = ['nullable', 'integer'];
            $rules['lines.*.payment_concept_id'] = ['nullable', 'exists:payment_concepts,id'];
            $rules['lines.*.chart_account_id'] = ['required', 'exists:chart_accounts,id'];
            $rules['lines.*.description'] = ['required', 'string', 'max:255'];
            $rules['lines.*.quantity'] = ['required', 'numeric', 'min:0.01'];
            $rules['lines.*.unit_price'] = ['required', 'numeric', 'min:0'];
            $rules['lines.*.tax_id'] = ['nullable', 'exists:taxes,id'];

            // Withholdings
            $rules['withholdings'] = ['nullable', 'array'];
            $rules['withholdings.*.id'] = ['nullable', 'integer'];
            $rules['withholdings.*.withholding_type_id'] = ['required', 'exists:withholding_types,id'];
            $rules['withholdings.*.base_amount'] = ['required', 'numeric', 'min:0'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'Debe agregar al menos una línea de detalle.',
            'lines.*.chart_account_id.required' => 'Debe seleccionar una cuenta contable para cada línea.',
            'lines.*.description.required' => 'La descripción es requerida para cada línea.',
            'lines.*.quantity.required' => 'La cantidad es requerida para cada línea.',
            'lines.*.unit_price.required' => 'El precio unitario es requerido para cada línea.',
        ];
    }
}
