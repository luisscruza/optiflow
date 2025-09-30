<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ProductImport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

final class MapColumnsRequest extends FormRequest
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
        $availableFields = array_column(ProductImport::getAvailableFields(), 'key');
        $stockFields = array_column(ProductImport::getStockFields(), 'key');
        $allFields = array_merge($availableFields, $stockFields, ['none']);

        return [
            'column_mapping' => ['required', 'array', 'min:1'],
            'column_mapping.*' => ['nullable', 'string', 'in:'.implode(',', $allFields)],
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
            'column_mapping.required' => 'Column mapping is required.',
            'column_mapping.array' => 'Column mapping must be an array.',
            'column_mapping.min' => 'At least one column must be mapped.',
            'column_mapping.*.in' => 'Invalid field mapping selected.',
            'workspaces.required' => 'At least one workspace must be selected.',
            'workspaces.array' => 'Workspaces must be an array.',
            'workspaces.min' => 'At least one workspace must be selected.',
            'workspaces.*.exists' => 'Selected workspace does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $allData = $validator->getData();
            $columnMapping = $allData['column_mapping'] ?? [];
            $mappedFields = array_filter($columnMapping, fn ($value) => $value !== 'none');

            // Ensure required fields are mapped
            $requiredFields = ['name'];
            $missingRequired = [];

            foreach ($requiredFields as $field) {
                if (! in_array($field, $mappedFields)) {
                    $missingRequired[] = $field;
                }
            }

            if (! empty($missingRequired)) {
                // Get field labels for better error messages
                $availableFields = collect(ProductImport::getAvailableFields())->keyBy('key');
                $fieldNames = implode(', ', array_map(fn ($field) => $availableFields[$field]['label'] ?? $field, $missingRequired));
                $validator->errors()->add('column_mapping', "Required fields must be mapped: {$fieldNames}");
            }

            // Check for duplicate mappings (excluding 'none')
            $mappedFields = array_filter($mappedFields, fn ($value) => $value !== 'none');
            $duplicates = array_diff_assoc($mappedFields, array_unique($mappedFields));

            if (! empty($duplicates)) {
                $validator->errors()->add('column_mapping', 'Each field can only be mapped once.');
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'column_mapping' => 'column mapping',
            'workspaces' => 'workspaces',
        ];
    }
}
