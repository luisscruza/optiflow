<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class StorePrescriptionAddendumRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->can(Permission::PrescriptionsEdit);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'pin' => ['required', 'digits_between:4,6'],
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
            'content.required' => 'Escribe la nota aclaratoria.',
            'content.max' => 'La adenda no puede superar los 5000 caracteres.',
            'pin.required' => 'Ingresa el PIN del evaluador.',
            'pin.digits_between' => 'El PIN debe tener entre 4 y 6 dígitos.',
        ];
    }
}
