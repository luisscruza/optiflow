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
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'prescription_id' => ['nullable', 'integer', 'exists:prescriptions,id'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['nullable'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
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
            'contact_id.required' => 'El cliente es obligatorio.',
            'contact_id.exists' => 'El cliente seleccionado no existe.',
            'invoice_id.exists' => 'La factura seleccionada no existe.',
            'prescription_id.exists' => 'La receta seleccionada no existe.',
            'priority.in' => 'La prioridad debe ser: baja, media, alta o urgente.',
            'due_date.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'images.max' => 'No se pueden subir más de 10 imágenes.',
            'images.*.image' => 'El archivo debe ser una imagen.',
            'images.*.mimes' => 'La imagen debe ser de tipo: jpeg, png, gif o webp.',
            'images.*.max' => 'Cada imagen no puede exceder 10MB.',
        ];
    }
}
