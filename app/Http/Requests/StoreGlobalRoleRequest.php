<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

final class StoreGlobalRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only business owners/admins can manage global roles
        return $this->user()?->business_role === UserRole::Owner
            || $this->user()?->business_role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es requerido.',
            'name.max' => 'El nombre del rol no debe exceder 255 caracteres.',
            'permissions.required' => 'Debe seleccionar al menos un permiso.',
            'permissions.min' => 'Debe seleccionar al menos un permiso.',
            'permissions.*.exists' => 'El permiso seleccionado no es válido.',
        ];
    }
}
