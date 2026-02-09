<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class StockAdjustmentRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'workspace_id' => [
                'nullable',
                'integer',
                'exists:workspaces,id',
            ],
            'adjustment_type' => [
                'required',
                'string',
                Rule::in(['set_quantity', 'add_quantity', 'remove_quantity']),
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],
            'reason' => [
                'required',
                'string',
                'max:500',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:100',
            ],
            'unit_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'redirect_back' => [
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
            'product_id' => 'product',
            'workspace_id' => 'workspace',
            'adjustment_type' => 'adjustment type',
            'unit_cost' => 'unit cost',
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
            'adjustment_type.in' => 'The adjustment type must be one of: set quantity, add quantity, or remove quantity.',
            'quantity.min' => 'The quantity must be a positive number.',
            'unit_cost.min' => 'The unit cost must be a positive number.',
        ];
    }
}
