<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignUserToWorkspaceRequest extends FormRequest
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
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail): void {
                    $email = request('email');
                    $existingUser = User::query()->where('email', $email)->first();

                    if (! $existingUser && empty($value)) {
                        $fail('El nombre es obligatorio para nuevos usuarios.');
                    }
                },
            ],
            'password' => [
                'sometimes',
                'string',
                'min:8',
                'confirmed',
                function ($attribute, $value, $fail): void {
                    $email = request('email');
                    $existingUser = User::query()->where('email', $email)->first();

                    if (! $existingUser && empty($value)) {
                        $fail('La contraseña es obligatoria para nuevos usuarios.');
                    }
                },
            ],
            'password_confirmation' => [
                'sometimes',
                'string',
                function ($attribute, $value, $fail): void {
                    $email = request('email');
                    $password = request('password');
                    $existingUser = User::query()->where('email', $email)->first();

                    if (! $existingUser && ! empty($password) && empty($value)) {
                        $fail('La confirmación de contraseña es obligatoria para nuevos usuarios.');
                    }
                },
            ],
            'workspace_assignments' => [
                'required',
                'array',
                'min:1',
            ],
            'workspace_assignments.*.workspace_id' => [
                'required',
                'integer',
                'exists:workspaces,id',
                function ($attribute, $value, $fail): void {
                    $email = request('email');
                    $user = User::query()->where('email', $email)->first();

                    if ($user) {
                        $workspace = Workspace::query()->find($value);
                        if ($workspace && $workspace->users()->where('user_id', $user->id)->exists()) {
                            $fail('El usuario ya es miembro de '.$workspace->name.'.');
                        }
                    }
                },
            ],
            'workspace_assignments.*.role' => [
                'required',
                Rule::enum(UserRole::class),
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->sometimes(['name', 'password', 'password_confirmation'], 'required', function ($input): bool {
            $existingUser = User::query()->where('email', $input->email ?? '')->first();

            return ! $existingUser;
        });
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
            'name.required' => 'El nombre es obligatorio para nuevos usuarios.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'password.required' => 'La contraseña es obligatoria para nuevos usuarios.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password_confirmation.required' => 'La confirmación de contraseña es obligatoria para nuevos usuarios.',
            'workspace_assignments.required' => 'Debe seleccionar al menos un workspace.',
            'workspace_assignments.min' => 'Debe seleccionar al menos un workspace.',
            'workspace_assignments.*.workspace_id.required' => 'El workspace es obligatorio.',
            'workspace_assignments.*.workspace_id.exists' => 'El workspace seleccionado no es válido.',
            'workspace_assignments.*.role.required' => 'El rol es obligatorio.',
            'workspace_assignments.*.role.enum' => 'El rol seleccionado no es válido.',
        ];
    }
}
