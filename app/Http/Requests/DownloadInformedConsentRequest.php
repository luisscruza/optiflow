<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class DownloadInformedConsentRequest extends FormRequest
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
            'prescription_id' => ['nullable', 'integer', 'exists:prescriptions,id'],
            'signer' => ['nullable', 'in:patient,representative'],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'representative_document_type' => ['nullable', 'in:cedula,pasaporte'],
            'representative_document_number' => ['nullable', 'string', 'max:50'],
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
            'prescription_id.exists' => 'La receta seleccionada no existe.',
            'signer.in' => 'Quien firma debe ser el paciente o su representante legal.',
            'representative_document_type.in' => 'El tipo de documento debe ser cédula o pasaporte.',
        ];
    }
}
