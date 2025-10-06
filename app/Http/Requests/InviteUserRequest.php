<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\UserInvitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Validation\Rule;

final class InviteUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();

        $workspace = Context::get('workspace');

        $userRole = $workspace->users()
            ->where('user_id', $user->id)
            ->first()?->pivot?->role;

        return $userRole === UserRole::Admin->value;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = Auth::user();
        $workspace = $user?->currentWorkspace;

        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                // Check if user already exists globally, if not we'll create them
                function ($attribute, $value, $fail) use ($workspace): void {
                    // Check if user already exists in this specific workspace
                    if ($workspace && $workspace->users()->where('email', $value)->exists()) {
                        $fail('Este usuario ya es miembro de la sucursal.');
                    }
                },
                // Can't have pending invitation for same email in this workspace
                function ($attribute, $value, $fail) use ($workspace): void {
                    if (! $workspace) {
                        return;
                    }

                    $pendingInvitation = UserInvitation::where('email', $value)
                        ->where('workspace_id', $workspace->id)
                        ->where('status', 'pending')
                        ->where('expires_at', '>', now())
                        ->exists();

                    if ($pendingInvitation) {
                        $fail('Ya existe una invitación pendiente para este correo.');
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'password_confirmation' => [
                'required',
                'string',
            ],
            'role' => [
                'required',
                Rule::enum(UserRole::class),
            ],
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
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser una dirección de correo válida.',
            'email.max' => 'El correo no puede tener más de 255 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password_confirmation.required' => 'La confirmación de contraseña es obligatoria.',
            'role.required' => 'El rol es obligatorio.',
            'role.enum' => 'El rol seleccionado no es válido.',
        ];
    }
}
