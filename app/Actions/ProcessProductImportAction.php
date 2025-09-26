<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImport;
use App\Models\Tax;
use App\Support\Slug;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ProcessProductImportAction
{
    public function __construct(
        private ValidateImportDataAction $validateImportDataAction,
        private SetInitialStockAction $setInitialStockAction
    ) {}

    /**
     * Process the product import.
     */
    public function handle(ProductImport $import, Collection $workspaces, array $stockMapping = []): array
    {
        if (! $import->import_data || ! $import->column_mapping) {
            throw new InvalidArgumentException('Import data and column mapping are required');
        }

        $import->markAsProcessing();

        return DB::transaction(function () use ($import, $workspaces, $stockMapping) {
            $enhancedImportData = $this->enhanceImportDataWithStockMapping(
                $import->import_data,
                $import->column_mapping,
                $workspaces,
                $stockMapping
            );

            $validationResult = $this->validateImportDataAction->handle($import, $enhancedImportData);

            $validRows = $validationResult['valid'];
            $errorRows = $validationResult['errors'];

            $importedProducts = [];
            $importErrors = [];
            $successful = 0;
            $errors = 0;

            foreach ($validRows as $index => $rowData) {
                try {
                    $product = $this->createProduct($rowData);
                    $this->handleStockData($product, $rowData, $workspaces, $stockMapping);

                    $importedProducts[] = $product->id;
                    $successful++;
                } catch (Exception $e) {
                    $errors++;
                    $importErrors[$index + 1] = [
                        'error' => $e->getMessage(),
                        'data' => $rowData,
                    ];
                }
            }

            $errors += count($errorRows);
            $totalProcessed = $successful + $errors;

            $import->updateProgress($totalProcessed, $successful, $errors);

            $summary = [
                'imported' => $successful,
                'errors' => $errors,
                'validation_errors' => count($errorRows),
                'processing_errors' => count($importErrors),
                'imported_product_ids' => $importedProducts,
            ];

            $import->update([
                'import_summary' => $summary,
                'validation_errors' => array_merge($errorRows, $importErrors),
            ]);

            if ($errors === 0) {
                $import->markAsCompleted();
            } else {
                $import->markAsFailed();
            }

            return $summary;
        });
    }

    /**
     * Create a product from row data.
     */
    private function createProduct(array $rowData): Product
    {
        // Handle tax mapping
        $defaultTaxId = null;
        if (isset($rowData['default_tax_rate']) && $rowData['default_tax_rate'] !== null) {
            $tax = Tax::where('rate', $rowData['default_tax_rate'])->first();
            $defaultTaxId = $tax?->id;
        }

        // Handle category
        $categoryId = null;
        if (isset($rowData['category']) && $rowData['category']) {
            $category = ProductCategory::firstOrCreate(
                ['name' => $rowData['category']],
                ['slug' => Slug::generateUniqueSlug($rowData['category'], ProductCategory::class)]
            );

            $categoryId = $category->id;
        }

        return Product::create([
            'name' => $rowData['name'],
            'sku' => $this->generateSku($rowData['name'], $rowData['sku']),
            'description' => $rowData['description'] ?? null,
            'price' => $rowData['price'],
            'cost' => $rowData['cost'] ?? null,
            'track_stock' => $rowData['track_stock'] ?? true,
            'default_tax_id' => $defaultTaxId,
            'product_category_id' => $categoryId,
        ]);
    }

    /**
     * Handle stock data for workspaces.
     */
    private function handleStockData(Product $product, array $rowData, Collection $workspaces, array $stockMapping = []): void
    {
        if (! $product->track_stock) {
            return;
        }


        // Check if we have workspace stock data from the enhanced mapping
        if (isset($rowData['workspace_stock_data'])) {
            foreach ($rowData['workspace_stock_data'] as $workspaceId => $stockData) {
                if (! empty($stockData['quantity'])) {
                    $stockInfo = [
                        'product_id' => $product->id,
                        'quantity' => $stockData['quantity'],
                        'minimum_quantity' => $stockData['minimum_quantity'] ?? 0,
                        'notes' => 'Initial stock from import',
                    ];

                    $this->setInitialStockAction->handle(
                        user: null,
                        data: $stockInfo,
                        workspace: $workspaces->firstWhere('id', $workspaceId) ?: null
                    );
                }
            }
        }
    }

    /**
     * Generate a SKU based on product name.
     */
    private function generateSku(string $productName, string $sku): string
    {

        if ($sku && ! empty($sku)) {
            return $sku;
        }

        $baseSku = preg_replace('/[^A-Za-z0-9]/', '', mb_strtoupper($productName));
        $baseSku = mb_substr($baseSku, 0, 8);
        $uniqueSuffix = mb_strtoupper(mb_substr(uniqid(), -4));

        return $baseSku.$uniqueSuffix;
    }

    /**
     * Enhance import data with workspace-specific stock mapping information.
     *
     * @param  array<int, array<string, mixed>>  $importData
     * @param  array<string, string>  $columnMapping
     * @param  array<string, array<string, string>>  $stockMapping
     * @return array<int, array<string, mixed>>
     */
    private function enhanceImportDataWithStockMapping(array $importData, array $columnMapping, Collection $workspaces, array $stockMapping): array
    {
        $enhancedData = [];

        foreach ($importData as $index => $row) {
            // Start with the basic mapped row data
            $mappedRow = $this->mapRowDataBasic($row, $columnMapping);

            // Add workspace-specific stock data
            $mappedRow['workspace_stock_data'] = [];

            foreach ($workspaces as $workspace) {
                $workspaceId = (string) $workspace->id;
                $workspaceStockMapping = $stockMapping[$workspaceId] ?? [];

                $stockData = [];

                // Map stock fields for this workspace
                foreach ($workspaceStockMapping as $stockField => $excelColumn) {
                    if (isset($row[$excelColumn])) {
                        switch ($stockField) {
                            case 'quantity':
                                $stockData['quantity'] = (int) $row[$excelColumn];
                                break;
                            case 'minimum_quantity':
                                $stockData['minimum_quantity'] = (int) $row[$excelColumn];
                                break;
                            case 'maximum_quantity':
                                $stockData['maximum_quantity'] = (int) $row[$excelColumn];
                                break;
                        }
                    }
                }

                $mappedRow['workspace_stock_data'][$workspaceId] = $stockData;
            }

            // If we have quantity data from any workspace, add it to the main row for validation
            if (! empty($mappedRow['workspace_stock_data'])) {
                // Find the first workspace with quantity data
                foreach ($mappedRow['workspace_stock_data'] as $stockData) {
                    if (isset($stockData['quantity'])) {
                        $mappedRow['quantity'] = $stockData['quantity'];
                        break;
                    }
                }
            }

            $enhancedData[] = $mappedRow;
        }

        return $enhancedData;
    }

    /**
     * Map raw row data to product fields based on column mapping (basic version).
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $columnMapping
     * @return array<string, mixed>
     */
    private function mapRowDataBasic(array $row, array $columnMapping): array
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
                        $mapped[$productField] = is_string($value) ? trim($value) : $value;
                }
            }
        }

        return $mapped;
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

        $stringValue = mb_strtolower(trim((string) $value));

        return in_array($stringValue, ['true', 'yes', '1', 'sí', 'verdadero', 'on']);
    }
}
