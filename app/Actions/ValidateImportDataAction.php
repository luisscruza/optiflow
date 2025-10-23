<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ProductImport;
use App\Models\Tax;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

final readonly class ValidateImportDataAction
{
    /**
     * Validate import data and return validation results.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array{valid: array<int, array<string, mixed>>, errors: array<int, array<string, array<int, string>>>}
     */
    public function handle(ProductImport $import, array $data): array
    {
        if (! $import->column_mapping) {
            throw new InvalidArgumentException('Column mapping is required for validation');
        }

        $validRows = [];
        $errorRows = [];

        foreach ($data as $index => $row) {
            // Check if row is already mapped (enhanced data)
            if (isset($row['workspace_stock_data'])) {
                // Data is already enhanced/mapped, use it directly
                $mappedRow = $row;
            } else {
                // Original behavior - map the data
                $mappedRow = $this->mapRowData($row, $import->column_mapping);
            }

            $validationResult = $this->validateRow($mappedRow);

            if (empty($validationResult['errors'])) {
                $validRows[] = $mappedRow;
            } else {
                $errorRows[$index + 1] = $validationResult['errors'];
            }
        }

        return [
            'valid' => $validRows,
            'errors' => $errorRows,
        ];
    }

    /**
     * Map raw row data to product fields based on column mapping.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $columnMapping
     * @return array<string, mixed>
     */
    private function mapRowData(array $row, array $columnMapping): array
    {
        $mapped = [];

        foreach ($columnMapping as $excelColumn => $productField) {
            if ($productField && isset($row[$excelColumn])) {
                $value = $row[$excelColumn];

                switch ($productField) {
                    case 'price':
                    case 'cost':
                        $mapped[$productField] = $this->parseNumericValue($value);
                        break;
                    case 'track_stock':
                    case 'allow_negative_stock':
                        $mapped[$productField] = $this->parseBooleanValue($value);
                        break;
                    case 'default_tax_rate':
                        $mapped['default_tax_rate'] = $this->parseNumericValue($value);
                        break;
                    default:
                        $mapped[$productField] = is_string($value) ? mb_trim($value) : $value;
                }
            }
        }

        return $mapped;
    }

    /**
     * Validate a single row of data.
     *
     * @param  array<string, mixed>  $row
     * @return array{data: array<string, mixed>, errors: array<string, array<int, string>>}
     */
    private function validateRow(array $row): array
    {
        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            'allow_negative_stock' => ['nullable', 'boolean'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'quantity' => [
                'required_if:track_stock,true',
                'nullable',
                'numeric',
                Rule::when(
                    empty($row['allow_negative_stock']) || $row['allow_negative_stock'] === false,
                    ['min:0']
                ),
            ],
            'minimum_quantity' => ['nullable', 'numeric', 'min:0'],
        ], [
            'name.required' => 'El nombre del producto es obligatorio.',
            'sku.required' => 'El código del producto es obligatorio.',
            'sku.unique' => 'Ya existe un producto con este código.',
            'description.max' => 'La descripción del producto no puede tener más de 1000 caracteres.',
            'price.required' => 'El precio del producto es obligatorio.',
            'price.numeric' => 'El precio del producto debe ser un número.',
            'price.min' => 'El precio del producto no puede ser negativo.',
            'cost.numeric' => 'El costo del producto debe ser un número.',
            'cost.min' => 'El costo del producto no puede ser negativo.',
            'track_stock.boolean' => 'El valor de rastrear inventario debe ser verdadero o falso.',
            'allow_negative_stock.boolean' => 'El valor de permitir inventario negativo debe ser verdadero o falso.',
            'default_tax_rate.numeric' => 'La tasa de impuesto por defecto debe ser un número.',
            'default_tax_rate.min' => 'La tasa de impuesto por defecto no puede ser negativa.',
            'default_tax_rate.max' => 'La tasa de impuesto por defecto no puede ser mayor que 100.',
            'category.max' => 'El nombre de la categoría no puede tener más de 255 caracteres.',
            'quantity.numeric' => 'La cantidad inicial debe ser un número.',
            'quantity.min' => 'La cantidad inicial no puede ser negativa.',
            'minimum_quantity.numeric' => 'La cantidad mínima debe ser un número.',
            'minimum_quantity.min' => 'La cantidad mínima no puede ser negativa.',
            'quantity.required_if' => 'La cantidad inicial es obligatoria si se rastrea inventario.',
        ]);

        if (isset($row['default_tax_rate']) && $row['default_tax_rate'] !== null) {

            $taxExists = Tax::query()->where('rate', $row['default_tax_rate'])->exists();

            if (! $taxExists) {
                $validator->after(function ($validator) use ($row): void {
                    $validator->errors()->add('default_tax_rate', "La tasa de impuesto por defecto '{$row['default_tax_rate']}' no existe. Debe crearse antes de importar productos con esta tasa.");
                });
            }
        }

        return [
            'data' => $row,
            'errors' => $validator->errors()->toArray(),
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

        // Remove currency symbols and commas
        $cleaned = preg_replace('/[^\d.-]/', '', (string) $value);

        if ($cleaned === '') {
            return null;
        }

        return (float) $cleaned;
    }

    /**
     * Parse a boolean value from various formats.
     */
    private function parseBooleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        $stringValue = mb_strtolower(mb_trim((string) $value));

        return in_array($stringValue, ['true', 'yes', '1', 'sí', 'verdadero', 'on']);
    }
}
