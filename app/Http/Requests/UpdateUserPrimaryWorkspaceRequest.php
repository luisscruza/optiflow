<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserPrimaryWorkspaceRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->hasBusinessRole(UserRole::Owner) || $user->hasBusinessRole(UserRole::Admin);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => ['nullable', 'integer', 'exists:workspaces,id'],
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
            'workspace_id.exists' => 'La sucursal seleccionada no existe.',
        ];
    }
}
