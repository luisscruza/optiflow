<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateClinicalPinRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->can(Permission::ContactsView);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_pin' => ['nullable', 'digits_between:4,6'],
            'pin' => ['required', 'digits_between:4,6', 'confirmed'],
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
            'pin.required' => 'Ingresa el nuevo PIN.',
            'pin.digits_between' => 'El PIN debe tener entre 4 y 6 dígitos.',
            'pin.confirmed' => 'Los PIN no coinciden.',
        ];
    }
}
