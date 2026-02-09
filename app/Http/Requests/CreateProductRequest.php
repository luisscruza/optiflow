<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'product_type' => ['required', Rule::enum(ProductType::class)],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'cost' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'track_stock' => ['boolean'],
            'allow_negative_stock' => ['boolean'],
            'default_tax_id' => ['nullable', 'exists:taxes,id'],
            'initial_quantity' => ['nullable', 'numeric', 'min:0'],
            'minimum_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'workspace_initial_quantities' => ['nullable', 'array'],
            'workspace_initial_quantities.*' => ['nullable', 'numeric', 'min:0'],
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
            'product_type.required' => 'Product type is required.',
            'initial_quantity.min' => 'Initial quantity must be greater than or equal to 0.',
            'minimum_quantity.min' => 'Minimum quantity must be greater than or equal to 0.',
            'unit_cost.min' => 'Unit cost must be greater than or equal to 0.',
            'workspace_initial_quantities.*.min' => 'Initial quantity per workspace must be greater than or equal to 0.',
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
            'product_type' => 'product type',
            'track_stock' => 'stock tracking',
            'allow_negative_stock' => 'allow negative stock',
            'initial_quantity' => 'initial quantity',
            'minimum_quantity' => 'minimum quantity',
            'unit_cost' => 'unit cost',
            'workspace_initial_quantities' => 'workspace initial quantities',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $productType = $this->input('product_type', ProductType::Product->value);

        $this->merge([
            'product_type' => $productType,
            'track_stock' => $productType === ProductType::Product->value
                ? $this->boolean('track_stock', true)
                : false,
            'allow_negative_stock' => $this->boolean('allow_negative_stock', false),
            'workspace_initial_quantities' => $productType === ProductType::Product->value
                ? $this->input('workspace_initial_quantities', [])
                : [],
        ]);
    }
}
