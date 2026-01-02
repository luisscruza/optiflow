<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateWorkflowJobRequest extends FormRequest
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
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'canceled_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['nullable'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
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
            'invoice_id.exists' => 'La factura seleccionada no existe.',
            'contact_id.exists' => 'El contacto seleccionado no existe.',
            'priority.in' => 'La prioridad debe ser: baja, media, alta o urgente.',
            'due_date.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'images.max' => 'No se pueden subir más de 10 imágenes.',
            'images.*.image' => 'El archivo debe ser una imagen.',
            'images.*.mimes' => 'La imagen debe ser de tipo: jpeg, png, gif o webp.',
            'images.*.max' => 'Cada imagen no puede exceder 10MB.',
            'remove_images.*.integer' => 'ID de imagen inválido.',
        ];
    }
}
