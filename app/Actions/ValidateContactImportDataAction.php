<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactType;
use App\Enums\Gender;
use App\Enums\IdentificationType;
use App\Models\ContactImport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Throwable;

final readonly class ValidateContactImportDataAction
{
    /**
     * Validate import data and return validation results.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array{valid: array<int, array{row: int, data: array<string, mixed>}>, errors: array<int, array{row: int, field: string, message: string}>}
     */
    public function handle(ContactImport $import, array $data, int $rowOffset = 0): array
    {
        if (! $import->column_mapping) {
            throw new InvalidArgumentException('Column mapping is required for validation');
        }

        $validRows = [];
        $errorRows = [];

        foreach ($data as $index => $row) {
            $mappedRow = $this->mapRowData($row, $import->column_mapping);
            $validationResult = $this->validateRow($mappedRow, $rowOffset + $index + 1);

            if ($validationResult['errors'] === []) {
                $validRows[] = [
                    'row' => $rowOffset + $index + 1,
                    'data' => $mappedRow,
                ];
            } else {
                $errorRows = array_merge($errorRows, $validationResult['errors']);
            }
        }

        return [
            'valid' => $validRows,
            'errors' => $errorRows,
        ];
    }

    /**
     * Map raw row data to contact fields based on column mapping.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $columnMapping
     * @return array<string, mixed>
     */
    private function mapRowData(array $row, array $columnMapping): array
    {
        $mapped = [];

        foreach ($columnMapping as $csvColumn => $contactField) {
            if (! $contactField || $contactField === 'none' || ! array_key_exists($csvColumn, $row)) {
                continue;
            }

            $value = $row[$csvColumn];

            switch ($contactField) {
                case 'credit_limit':
                    $mapped[$contactField] = $this->parseNumericValue($value);
                    break;
                case 'birth_date':
                case 'created_at':
                case 'updated_at':
                    $mapped[$contactField] = $this->parseDateValue($value);
                    break;
                case 'gender':
                    $mapped[$contactField] = $this->parseGenderValue($value);
                    break;
                case 'identification_type':
                    $mapped[$contactField] = $this->parseIdentificationTypeValue($value);
                    break;
                case 'status':
                    $mapped[$contactField] = $this->parseStatusValue($value);
                    break;
                case 'contact_type':
                    $mapped[$contactField] = $this->parseContactTypeValue($value);
                    break;
                case 'metadata':
                    $mapped[$contactField] = $this->parseMetadataValue($value);
                    break;
                default:
                    $mapped[$contactField] = is_string($value) ? mb_trim($value) : $value;
            }
        }

        return $mapped;
    }

    /**
     * Validate a single row of data.
     *
     * @param  array<string, mixed>  $row
     * @return array{data: array<string, mixed>, errors: array<int, array{row: int, field: string, message: string}>}
     */
    private function validateRow(array $row, int $rowNumber): array
    {
        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_primary' => ['nullable', 'string', 'max:20'],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'identification_type' => ['nullable', Rule::enum(IdentificationType::class)],
            'identification_number' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'observations' => ['nullable', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:male,female,-'],
            'contact_type' => ['nullable', 'string', Rule::in([ContactType::Customer->value])],
            'created_at' => ['nullable', 'date'],
            'updated_at' => ['nullable', 'date'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'credit_limit.numeric' => 'El límite de crédito debe ser un número.',
            'credit_limit.min' => 'El límite de crédito no puede ser negativo.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'gender.in' => 'El género debe ser masculino, femenino o no especificado.',
            'status.in' => 'El estado debe ser activo o inactivo.',
            'contact_type.in' => 'El tipo de contacto debe ser cliente.',
        ]);

        $errors = [];
        foreach ($validator->errors()->messages() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => $field,
                    'message' => $message,
                ];
            }
        }

        return [
            'data' => $row,
            'errors' => $errors,
        ];
    }

    /**
     * Parse a numeric value from various formats.
     */
    private function parseNumericValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = preg_replace('/[^\d.-]/', '', (string) $value);

        if ($cleaned === '') {
            return null;
        }

        return (float) $cleaned;
    }

    /**
     * Parse a date value.
     */
    private function parseDateValue(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Parse a gender value.
     */
    private function parseGenderValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = mb_strtolower(mb_trim((string) $value));

        return match ($normalized) {
            'male', 'm', 'masculino' => Gender::Male->value,
            'female', 'f', 'femenino' => Gender::Female->value,
            '-', 'no especificado', 'none' => Gender::NotSpecified->value,
            default => $normalized,
        };
    }

    /**
     * Parse an identification type value.
     */
    private function parseIdentificationTypeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = mb_strtolower(mb_trim((string) $value));

        return match ($normalized) {
            'cedula', 'cédula' => IdentificationType::Cedula->value,
            'rnc' => IdentificationType::RNC->value,
            'pasaporte' => IdentificationType::Pasaporte->value,
            default => $normalized,
        };
    }

    /**
     * Parse status value.
     */
    private function parseStatusValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = mb_strtolower(mb_trim((string) $value));

        return match ($normalized) {
            'activo' => 'active',
            'inactivo' => 'inactive',
            default => $normalized,
        };
    }

    /**
     * Parse contact type value.
     */
    private function parseContactTypeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = mb_strtolower(mb_trim((string) $value));

        return match ($normalized) {
            'cliente' => ContactType::Customer->value,
            default => $normalized,
        };
    }

    /**
     * Parse metadata value.
     */
    private function parseMetadataValue(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
