<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductBulkUpdate;
use App\Models\Tax;
use App\Models\User;
use App\Models\Workspace;
use App\Scopes\ActiveProductScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;

final class AnalyzeProductBulkUpdateAction
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     preview_rows: array<int, array<string, mixed>>,
     *     validation_errors: array<int, array{row: int, field: string, message: string}>,
     *     summary: array{products_updated: int, unchanged_rows: int, stock_adjustments_created: int, rows_failed: int},
     *     prepared_rows: array<int, array{row: int, product_id: int, updates: array<string, mixed>, stock_targets: array<int, array{workspace_id: int, quantity: float}>}>
     * }
     */
    public function handle(array $rows, User $user): array
    {
        $workspaces = $this->availableWorkspaces($user);
        $workspaceColumnMap = $workspaces->mapWithKeys(
            fn (Workspace $workspace): array => [ProductBulkUpdate::workspaceStockColumnName($workspace) => $workspace],
        );

        $productsBySku = Product::query()
            ->withoutGlobalScope(ActiveProductScope::class)
            ->whereIn('sku', $this->skuLookupValues($rows))
            ->with([
                'defaultTax:id,name,rate',
                'stocks' => fn ($query) => $query
                    ->withoutWorkspaceScope()
                    ->whereIn('workspace_id', $workspaces->pluck('id')->all())
                    ->select(['id', 'product_id', 'workspace_id', 'quantity']),
            ])
            ->get()
            ->keyBy('sku');

        $taxes = Tax::query()->get()->keyBy(fn (Tax $tax): string => $this->taxKey($tax->name, (float) $tax->rate));

        $previewRows = [];
        $preparedRows = [];
        $validationErrors = [];
        $summary = [
            'products_updated' => 0,
            'unchanged_rows' => 0,
            'stock_adjustments_created' => 0,
            'rows_failed' => 0,
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $preparedRow = $this->prepareRow($row, $rowNumber, $productsBySku, $taxes, $workspaceColumnMap);

                $previewRows[] = [
                    'row' => $rowNumber,
                    'sku' => $preparedRow['sku'],
                    'product_name' => $preparedRow['product_name'],
                    'changes' => $preparedRow['changes'],
                    'has_changes' => $preparedRow['changes'] !== [],
                ];

                $preparedRows[] = [
                    'row' => $rowNumber,
                    'product_id' => $preparedRow['product_id'],
                    'updates' => $preparedRow['updates'],
                    'stock_targets' => $preparedRow['stock_targets'],
                ];

                if ($preparedRow['changes'] === []) {
                    $summary['unchanged_rows']++;
                } else {
                    $summary['products_updated']++;
                    $summary['stock_adjustments_created'] += count($preparedRow['stock_targets']);
                }
            } catch (RuntimeException $exception) {
                $summary['rows_failed']++;
                $validationErrors[] = [
                    'row' => $rowNumber,
                    'field' => 'row',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'preview_rows' => $previewRows,
            'validation_errors' => $validationErrors,
            'summary' => $summary,
            'prepared_rows' => $preparedRows,
        ];
    }

    /**
     * @param  Collection<int, Product>  $productsBySku
     * @param  Collection<int, Tax>  $taxes
     * @param  Collection<string, Workspace>  $workspaceColumnMap
     * @return array{sku: string, product_name: string, product_id: int, changes: array<int, array{field: string, from: string|null, to: string|null}>, updates: array<string, mixed>, stock_targets: array<int, array{workspace_id: int, quantity: float}>}
     */
    private function prepareRow(array $row, int $rowNumber, Collection $productsBySku, Collection $taxes, Collection $workspaceColumnMap): array
    {
        $sku = $this->stringValue($row['SKU'] ?? null);

        if ($sku === '') {
            throw new RuntimeException('SKU is required.');
        }

        $product = $this->findProductBySku($productsBySku, $sku);

        if (! $product instanceof Product) {
            throw new RuntimeException("Unknown SKU [{$sku}].");
        }

        $changes = [];
        $updates = [];
        $stockTargets = [];

        if (array_key_exists('NAME', $row)) {
            $name = $this->stringValue($row['NAME']);

            if ($name === '') {
                throw new RuntimeException('NAME cannot be empty.');
            }

            $this->recordChange($changes, 'NAME', $product->name, $name);
            $updates['name'] = $name;
        }

        if (array_key_exists('DESCRIPTION', $row)) {
            $description = $this->nullableStringValue($row['DESCRIPTION']);
            $this->recordChange($changes, 'DESCRIPTION', $product->description, $description);
            $updates['description'] = $description;
        }

        if (array_key_exists('PRODUCT_TYPE', $row)) {
            $productType = ProductType::tryFrom(Str::lower($this->stringValue($row['PRODUCT_TYPE'])));

            if (! $productType instanceof ProductType) {
                throw new RuntimeException('PRODUCT_TYPE must be product or service.');
            }

            $this->recordChange($changes, 'PRODUCT_TYPE', $product->product_type->value, $productType->value);
            $updates['product_type'] = $productType;
        }

        if (array_key_exists('PRICE', $row)) {
            $price = $this->requiredNumericValue($row['PRICE'], 'PRICE');
            $this->recordChange($changes, 'PRICE', $this->formatNumeric((float) $product->price), $this->formatNumeric($price));
            $updates['price'] = $price;
        }

        if (array_key_exists('COST', $row)) {
            $cost = $this->nullableNumericValue($row['COST'], 'COST');
            $this->recordChange($changes, 'COST', $this->formatNullableNumeric($product->cost), $this->formatNullableNumeric($cost));
            $updates['cost'] = $cost;
        }

        if (array_key_exists('TRACK_STOCK', $row)) {
            $trackStock = $this->booleanValue($row['TRACK_STOCK'], 'TRACK_STOCK');
            $this->recordChange($changes, 'TRACK_STOCK', $product->track_stock ? 'true' : 'false', $trackStock ? 'true' : 'false');
            $updates['track_stock'] = $trackStock;
        }

        if (array_key_exists('ALLOW_NEGATIVE_STOCK', $row)) {
            $allowNegativeStock = $this->booleanValue($row['ALLOW_NEGATIVE_STOCK'], 'ALLOW_NEGATIVE_STOCK');
            $this->recordChange($changes, 'ALLOW_NEGATIVE_STOCK', $product->allow_negative_stock ? 'true' : 'false', $allowNegativeStock ? 'true' : 'false');
            $updates['allow_negative_stock'] = $allowNegativeStock;
        }

        if (($updates['product_type'] ?? $product->product_type) === ProductType::Service) {
            $this->recordChange($changes, 'TRACK_STOCK', $product->track_stock ? 'true' : 'false', 'false');
            $updates['track_stock'] = false;
        }

        if (array_key_exists('STATUS', $row)) {
            $isActive = $this->statusValue($row['STATUS']);
            $this->recordChange($changes, 'STATUS', $product->is_active ? 'active' : 'inactive', $isActive ? 'active' : 'inactive');
            $updates['is_active'] = $isActive;
        }

        $taxName = array_key_exists('TAX_1_NAME', $row) ? $this->nullableStringValue($row['TAX_1_NAME']) : null;
        $taxRateProvided = array_key_exists('TAX_1_RATE', $row);
        $taxRate = $taxRateProvided ? $this->nullableNumericValue($row['TAX_1_RATE'], 'TAX_1_RATE') : null;

        if (array_key_exists('TAX_1_NAME', $row) || $taxRateProvided) {
            if ($taxName === null && $taxRate === null) {
                $this->recordChange($changes, 'TAX_1', $this->taxDisplay($product->defaultTax?->name, $product->defaultTax?->rate), null);
                $updates['default_tax_id'] = null;
            } elseif ($taxName === null || $taxRate === null) {
                throw new RuntimeException('TAX_1_NAME and TAX_1_RATE must both be filled or both be blank.');
            } else {
                $tax = $taxes->get($this->taxKey($taxName, $taxRate));

                if (! $tax instanceof Tax) {
                    throw new RuntimeException('No tax matches TAX_1_NAME and TAX_1_RATE.');
                }

                $this->recordChange(
                    $changes,
                    'TAX_1',
                    $this->taxDisplay($product->defaultTax?->name, $product->defaultTax?->rate),
                    $this->taxDisplay($tax->name, $tax->rate),
                );
                $updates['default_tax_id'] = $tax->id;
            }
        }

        $trackStockAfterUpdate = $updates['track_stock'] ?? $product->track_stock;
        $stocksByWorkspace = $product->stocks->keyBy('workspace_id');

        foreach ($workspaceColumnMap as $column => $workspace) {
            if (! array_key_exists($column, $row)) {
                continue;
            }

            $rawStockValue = $row[$column];

            if ($this->stringValue($rawStockValue) === '') {
                continue;
            }

            if ($trackStockAfterUpdate !== true) {
                continue;
            }

            $targetStock = $this->requiredNumericValue($rawStockValue, $column);
            $currentStock = (float) ($stocksByWorkspace->get($workspace->id)?->quantity ?? 0);

            if (round($currentStock, 2) === round($targetStock, 2)) {
                continue;
            }

            $changes[] = [
                'field' => $column,
                'from' => $this->formatNumeric($currentStock),
                'to' => $this->formatNumeric($targetStock),
            ];

            $stockTargets[] = [
                'workspace_id' => $workspace->id,
                'quantity' => $targetStock,
            ];
        }

        return [
            'sku' => $product->sku,
            'product_name' => $product->name,
            'product_id' => $product->id,
            'changes' => array_values($changes),
            'updates' => $updates,
            'stock_targets' => $stockTargets,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string>
     */
    private function skuLookupValues(array $rows): array
    {
        $values = [];

        foreach ($rows as $row) {
            foreach ($this->skuCandidates($row['SKU'] ?? null) as $candidate) {
                $values[$candidate] = $candidate;
            }
        }

        return array_values($values);
    }

    /**
     * @param  Collection<int, Product>  $productsBySku
     */
    private function findProductBySku(Collection $productsBySku, mixed $value): ?Product
    {
        foreach ($this->skuCandidates($value) as $candidate) {
            $product = $productsBySku->get($candidate);

            if ($product instanceof Product) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function skuCandidates(mixed $value): array
    {
        $candidates = [];
        $rawValue = $this->stringValue($value);

        if ($rawValue === '') {
            return [];
        }

        $candidates[$rawValue] = $rawValue;

        if (preg_match('/^[+-]?\d+\.0+$/', $rawValue) === 1) {
            $trimmedInteger = preg_replace('/\.0+$/', '', $rawValue);

            if (is_string($trimmedInteger) && $trimmedInteger !== '') {
                $candidates[$trimmedInteger] = $trimmedInteger;
            }
        }

        $scientificValue = $this->scientificNotationToPlainString($rawValue);

        if ($scientificValue !== null) {
            $candidates[$scientificValue] = $scientificValue;
        }

        return array_values($candidates);
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function availableWorkspaces(User $user): Collection
    {
        $query = Workspace::query()->select(['id', 'name', 'slug', 'code'])->orderBy('name');

        if (! $user->can(Permission::ViewAllLocations)) {
            $query->whereIn('id', $user->workspaces()->select('workspaces.id'));
        }

        return $query->get();
    }

    private function stringValue(mixed $value): string
    {
        return mb_trim((string) ($value ?? ''));
    }

    private function nullableStringValue(mixed $value): ?string
    {
        $normalized = $this->stringValue($value);

        return $normalized === '' ? null : $normalized;
    }

    private function requiredNumericValue(mixed $value, string $field): float
    {
        $normalized = $this->nullableNumericValue($value, $field);

        if ($normalized === null) {
            throw new RuntimeException("{$field} must be a valid number.");
        }

        return $normalized;
    }

    private function nullableNumericValue(mixed $value, string $field): ?float
    {
        $normalized = $this->stringValue($value);

        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(',', '', $normalized);

        if (! is_numeric($normalized)) {
            throw new RuntimeException("{$field} must be a valid number.");
        }

        return round((float) $normalized, 2);
    }

    private function booleanValue(mixed $value, string $field): bool
    {
        $normalized = Str::lower($this->stringValue($value));

        return match ($normalized) {
            '1', 'true', 'yes', 'si', 'sí' => true,
            '0', 'false', 'no' => false,
            default => throw new RuntimeException("{$field} must be true/false or yes/no."),
        };
    }

    private function statusValue(mixed $value): bool
    {
        $normalized = Str::lower($this->stringValue($value));

        return match ($normalized) {
            'active', 'activo' => true,
            'inactive', 'inactivo' => false,
            default => throw new RuntimeException('STATUS must be active or inactive.'),
        };
    }

    private function taxKey(string $name, float $rate): string
    {
        return Str::lower(mb_trim($name)).'|'.number_format($rate, 2, '.', '');
    }

    private function taxDisplay(?string $name, float|int|string|null $rate): ?string
    {
        if ($name === null || $rate === null) {
            return null;
        }

        return $name.' ('.$this->formatNumeric((float) $rate).'%)';
    }

    /**
     * @param  array<int, array{field: string, from: string|null, to: string|null}>  $changes
     */
    private function recordChange(array &$changes, string $field, ?string $from, ?string $to): void
    {
        if ($from === $to) {
            return;
        }

        $changes[] = [
            'field' => $field,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function formatNumeric(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function formatNullableNumeric(float|int|string|null $value): ?string
    {
        return $value === null ? null : $this->formatNumeric((float) $value);
    }

    private function scientificNotationToPlainString(string $value): ?string
    {
        if (preg_match('/^([+-]?\d+(?:\.\d+)?)[eE]([+-]?\d+)$/', $value, $matches) !== 1) {
            return null;
        }

        $mantissa = $matches[1];
        $exponent = (int) $matches[2];
        $negative = str_starts_with($mantissa, '-');
        $mantissa = mb_ltrim($mantissa, '+-');
        [$integerPart, $fractionPart] = array_pad(explode('.', $mantissa, 2), 2, '');
        $digits = $integerPart.$fractionPart;
        $decimalPosition = mb_strlen($integerPart);
        $newDecimalPosition = $decimalPosition + $exponent;

        if ($newDecimalPosition < 0) {
            $digits = str_repeat('0', abs($newDecimalPosition)).$digits;
            $newDecimalPosition = 0;
        }

        if ($newDecimalPosition > mb_strlen($digits)) {
            $digits .= str_repeat('0', $newDecimalPosition - mb_strlen($digits));
        }

        if ($newDecimalPosition === 0) {
            $plain = '0.'.$digits;
        } elseif ($newDecimalPosition >= mb_strlen($digits)) {
            $plain = $digits;
        } else {
            $plain = mb_substr($digits, 0, $newDecimalPosition).'.'.mb_substr($digits, $newDecimalPosition);
        }

        $plain = mb_ltrim($plain, '0');

        if ($plain === '' || str_starts_with($plain, '.')) {
            $plain = '0'.$plain;
        }

        $plain = mb_rtrim(mb_rtrim($plain, '0'), '.');

        if ($plain === '') {
            $plain = '0';
        }

        return $negative ? '-'.$plain : $plain;
    }
}
