<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->current_workspace_id;
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
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'cost' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'track_stock' => ['boolean'],
            'default_tax_id' => ['nullable', 'exists:taxes,id'],
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
            'name.required' => 'Product name is required.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU is already in use.',
            'price.required' => 'Price is required.',
            'price.min' => 'Price must be greater than or equal to 0.',
            'cost.min' => 'Cost must be greater than or equal to 0.',
            'default_tax_id.exists' => 'The selected tax rate does not exist.',
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
            'default_tax_id' => 'default tax rate',
            'track_stock' => 'stock tracking',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'track_stock' => $this->boolean('track_stock', true),
        ]);
    }
}
