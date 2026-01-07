<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaxType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaxRequest extends FormRequest
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
        $taxId = $this->route('tax')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taxes', 'name')->ignore($taxId),
            ],
            'type' => [
                'required',
                Rule::enum(TaxType::class),
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
            'type' => 'tax type',
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
            'type.required' => 'The tax type is required.',
            'rate.required' => 'The tax rate is required.',
            'rate.numeric' => 'The tax rate must be a number.',
            'rate.min' => 'The tax rate cannot be negative.',
            'rate.max' => 'The tax rate cannot exceed 100%.',
        ];
    }
}
