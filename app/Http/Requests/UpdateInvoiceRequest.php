<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateInvoiceRequest extends FormRequest
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
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'document_subtype_id' => ['required', 'integer', 'exists:document_subtypes,id'],
            'ncf' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'payment_term' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_total' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'string'], // Frontend sends string IDs
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['required', 'numeric', 'min:0'],
            'items.*.taxes' => ['nullable', 'array'],
            'items.*.taxes.*.id' => ['required', 'integer', 'exists:taxes,id'],
            'items.*.taxes.*.rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.taxes.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['required', 'numeric', 'min:0'],
            'items.*.total' => ['required', 'numeric', 'min:0'],

            'salesmen_ids' => ['nullable', 'array'],
            'salesmen_ids.*' => ['integer', 'exists:salesmen,id'],
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
            'contact_id.required' => 'El cliente es requerido.',
            'contact_id.exists' => 'El cliente seleccionado no existe.',
            'document_subtype_id.required' => 'El tipo de documento es requerido.',
            'document_subtype_id.exists' => 'El tipo de documento seleccionado no existe.',
            'ncf.required' => 'El NCF es requerido.',
            'issue_date.required' => 'La fecha de emisión es requerida.',
            'due_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión.',

            'items.required' => 'Debe incluir al menos un item.',
            'items.min' => 'Debe incluir al menos un item.',
            'items.*.product_id.required' => 'El producto es requerido para cada item.',
            'items.*.product_id.exists' => 'El producto seleccionado no existe.',
            'items.*.description.required' => 'La descripción es requerida para cada item.',
            'items.*.quantity.required' => 'La cantidad es requerida para cada item.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.unit_price.required' => 'El precio unitario es requerido para cada item.',
            'items.*.discount_rate.required' => 'El descuento es requerido para cada item.',
            'items.*.discount_rate.min' => 'El descuento debe ser mayor o igual a 0.',
            'items.*.discount_rate.max' => 'El descuento no puede ser mayor al 100%.',
            'items.*.discount_amount.required' => 'El monto del descuento es requerido para cada item.',
            'items.*.taxes.*.id.required' => 'El ID del impuesto es requerido.',
            'items.*.taxes.*.id.exists' => 'El impuesto seleccionado no existe.',
            'items.*.taxes.*.rate.required' => 'La tasa del impuesto es requerida.',
            'items.*.taxes.*.amount.required' => 'El monto del impuesto es requerido.',
            'items.*.tax_amount.required' => 'El monto total de impuestos es requerido para cada item.',
            'items.*.total.required' => 'El total es requerido para cada item.',
            'items.*.unit_price.min' => 'El precio unitario debe ser mayor o igual a 0.',
            'items.*.discount.required' => 'El descuento es requerido para cada item.',
            'items.*.discount.min' => 'El descuento debe ser mayor o igual a 0.',
            'items.*.discount.max' => 'El descuento no puede ser mayor al 100%.',
            'items.*.tax_id.required' => 'El impuesto es requerido para cada item.',
            'items.*.tax_id.exists' => 'El impuesto seleccionado no existe.',
            'salesmen_ids.array' => 'Los vendedores deben ser un array.',
            'salesmen_ids.*.exists' => 'Uno de los vendedores seleccionados no existe.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_id' => 'cliente',
            'document_subtype_id' => 'tipo de documento',
            'document_number' => 'número de documento',
            'issue_date' => 'fecha de emisión',
            'due_date' => 'fecha de vencimiento',
            'notes' => 'notas',
            'status' => 'estado',
            'items' => 'items',
        ];
    }
}
