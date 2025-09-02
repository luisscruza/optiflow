<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class SetInitialStockRequest extends FormRequest
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
            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],
            'minimum_quantity' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'unit_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
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
            'minimum_quantity' => 'minimum stock level',
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
            'quantity.min' => 'The initial stock quantity cannot be negative.',
            'minimum_quantity.min' => 'The minimum stock level cannot be negative.',
            'unit_cost.min' => 'The unit cost must be a positive number.',
        ];
    }
}
