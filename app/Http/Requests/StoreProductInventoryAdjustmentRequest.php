<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class StoreProductInventoryAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can(Permission::InventoryAdjust);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'workspace_id' => [
                'required',
                'integer',
                'exists:workspaces,id',
            ],
            'adjustment_date' => [
                'required',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*' => [
                'required',
                'array:product_id,adjustment_type,quantity',
            ],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],
            'items.*.adjustment_type' => [
                'required',
                'string',
                Rule::in(['increment', 'decrement']),
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'workspace_id' => 'sucursal',
            'adjustment_date' => 'fecha',
            'items' => 'productos',
            'items.*.product_id' => 'producto',
            'items.*.adjustment_type' => 'tipo de ajuste',
            'items.*.quantity' => 'cantidad',
        ];
    }
}
