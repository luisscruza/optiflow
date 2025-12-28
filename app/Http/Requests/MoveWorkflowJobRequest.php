<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MoveWorkflowJobRequest extends FormRequest
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
            'workflow_stage_id.required' => 'La etapa de destino es obligatoria.',
            'workflow_stage_id.exists' => 'La etapa seleccionada no existe.',
        ];
    }
}
