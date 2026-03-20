<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Workspace;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloneAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_workspace_id' => [
                'required',
                'integer',
                Rule::exists(Workspace::class, 'id'),
                Rule::notIn([$this->user()?->current_workspace_id]),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->user()?->workspaces()->where('workspaces.id', $value)->exists()) {
                        $fail('Debes seleccionar una sucursal a la que tengas acceso.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_workspace_id.required' => 'Debes seleccionar una sucursal.',
            'target_workspace_id.not_in' => 'Selecciona una sucursal diferente a la actual.',
            'target_workspace_id.exists' => 'La sucursal seleccionada no es valida.',
        ];
    }
}
