<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ContactImport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateContactImportRequest extends FormRequest
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
        $availableFields = array_column(ContactImport::getAvailableFields(), 'key');
        $allFields = array_merge($availableFields, ['none']);

        return [
            'column_mapping' => ['required', 'array', 'min:1'],
            'column_mapping.*' => ['nullable', 'string', Rule::in($allFields)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mapping = $this->input('column_mapping', []);
            $mappedFields = array_values(array_filter($mapping, fn ($value): bool => $value !== 'none'));
            $requiredFields = array_filter(ContactImport::getAvailableFields(), fn (array $field): bool => $field['required'] === true);

            foreach ($requiredFields as $field) {
                if (! in_array($field['key'], $mappedFields, true)) {
                    $validator->errors()->add('column_mapping', "El campo {$field['label']} es obligatorio.");
                }
            }

            $duplicates = array_diff_assoc($mappedFields, array_unique($mappedFields));
            if ($duplicates !== []) {
                $validator->errors()->add('column_mapping', 'Cada campo solo puede mapearse una vez.');
            }
        });
    }
}
