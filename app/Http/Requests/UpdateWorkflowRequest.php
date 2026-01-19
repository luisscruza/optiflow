<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateWorkflowRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'invoice_requirement' => ['sometimes', 'nullable', 'string', 'in:optional,required'],
            'prescription_requirement' => ['sometimes', 'nullable', 'string', 'in:optional,required'],
            'fields' => ['sometimes', 'array'],
            'fields.*.id' => ['sometimes', 'nullable', 'uuid'],
            'fields.*.name' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.key' => ['required_with:fields', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/'],
            'fields.*.type' => ['required_with:fields', 'string', 'in:text,textarea,number,date,select,boolean'],
            'fields.*.mastertable_id' => ['nullable', 'integer', 'exists:mastertables,id'],
            'fields.*.is_required' => ['sometimes', 'boolean'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.default_value' => ['nullable', 'string', 'max:255'],
            'fields.*.position' => ['sometimes', 'integer', 'min:0'],
            'fields.*._destroy' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del flujo de trabajo es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
        ];
    }
}
