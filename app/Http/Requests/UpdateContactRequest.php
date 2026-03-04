<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ContactType;
use App\Enums\IdentificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateContactRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'contact_type' => ['required', Rule::enum(ContactType::class)],
            'identification_type' => ['nullable', Rule::enum(IdentificationType::class)],
            'identification_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_primary' => ['nullable', 'string', 'max:20'],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'observations' => ['nullable', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],

            // Address validation
            'address.province' => ['nullable', 'string', 'max:255'],
            'address.municipality' => ['nullable', 'string', 'max:255'],
            'address.country' => ['nullable', 'string', 'max:255'],
            'address.description' => ['nullable', 'string'],
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
            'name.required' => 'El nombre es obligatorio.',
            'contact_type.required' => 'El tipo de contacto es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'credit_limit.numeric' => 'El límite de crédito debe ser un número.',
            'credit_limit.min' => 'El límite de crédito no puede ser negativo.',
        ];
    }
}
