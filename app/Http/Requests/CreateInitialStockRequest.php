<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateInitialStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('track_stock', true);
                }),
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],
            'unit_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'note' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
