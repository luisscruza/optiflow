<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class StockTransferRequest extends FormRequest
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
        $currentWorkspaceId = Auth::user()->current_workspace_id;

        return [
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'from_workspace_id' => [
                'required',
                'integer',
                'exists:workspaces,id',
                Rule::exists('user_workspace', 'workspace_id')
                    ->where('user_id', Auth::id()),
            ],
            'to_workspace_id' => [
                'required',
                'integer',
                'exists:workspaces,id',
                'different:from_workspace_id',
                Rule::exists('user_workspace', 'workspace_id')
                    ->where('user_id', Auth::id()),
            ],
            'quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:100',
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
            'from_workspace_id' => 'source workspace',
            'to_workspace_id' => 'destination workspace',
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
            'quantity.gt' => 'The transfer quantity must be greater than zero.',
            'to_workspace_id.different' => 'The destination workspace must be different from the source workspace.',
            'from_workspace_id.exists' => 'You do not have access to the selected source workspace.',
            'to_workspace_id.exists' => 'You do not have access to the selected destination workspace.',
        ];
    }
}
