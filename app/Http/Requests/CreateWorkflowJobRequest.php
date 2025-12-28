<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateWorkflowJobRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'workflow_stage_id' => ['required', 'uuid', 'exists:workflow_stages,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
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
            'workflow_stage_id.required' => 'La etapa del flujo de trabajo es obligatoria.',
            'workflow_stage_id.exists' => 'La etapa seleccionada no existe.',
            'invoice_id.exists' => 'La factura seleccionada no existe.',
            'contact_id.exists' => 'El contacto seleccionado no existe.',
            'priority.in' => 'La prioridad debe ser: baja, media, alta o urgente.',
            'due_date.date' => 'La fecha de vencimiento debe ser una fecha válida.',
        ];
    }
}
