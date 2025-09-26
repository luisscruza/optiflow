<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class ProcessImportRequest extends FormRequest
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
            'workspaces' => ['required', 'array', 'min:1'],
            'workspaces.*' => ['exists:workspaces,id'],
            'stock_mapping' => ['sometimes', 'array'],
            'stock_mapping.*' => ['array'],
            'stock_mapping.*.*' => ['string'],
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
            'workspaces.required' => 'At least one workspace must be selected.',
            'workspaces.array' => 'Workspaces must be an array.',
            'workspaces.min' => 'At least one workspace must be selected.',
            'workspaces.*.exists' => 'Selected workspace does not exist.',
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
            'workspaces' => 'workspaces',
        ];
    }
}
