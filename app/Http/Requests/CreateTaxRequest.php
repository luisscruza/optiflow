<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTaxRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:taxes,name',
            ],
            'rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'is_default' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'tax name',
            'rate' => 'tax rate',
            'is_default' => 'default tax',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The tax name is required.',
            'name.unique' => 'A tax with this name already exists.',
            'rate.required' => 'The tax rate is required.',
            'rate.numeric' => 'The tax rate must be a number.',
            'rate.min' => 'The tax rate cannot be negative.',
            'rate.max' => 'The tax rate cannot exceed 100%.',
        ];
    }
}
