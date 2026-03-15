<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductBulkUpdate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ProcessProductBulkUpdateAction
{
    public function __construct(
        private ParseProductBulkUpdateCsvAction $parseProductBulkUpdateCsvAction,
        private AnalyzeProductBulkUpdateAction $analyzeProductBulkUpdateAction,
        private StockAdjustmentAction $stockAdjustmentAction,
    ) {}

    public function handle(ProductBulkUpdate $bulkUpdate, User $user): ProductBulkUpdate
    {
        $parsedFile = $this->parseProductBulkUpdateCsvAction->handle($bulkUpdate);
        $analysis = $this->analyzeProductBulkUpdateAction->handle($parsedFile['data'], $user);

        $bulkUpdate->update([
            'status' => ProductBulkUpdate::STATUS_PROCESSING,
            'headers' => $parsedFile['headers'],
            'preview_rows' => $analysis['preview_rows'],
            'validation_errors' => $analysis['validation_errors'],
            'summary' => $analysis['summary'],
            'total_rows' => $parsedFile['total_rows'],
            'processed_rows' => 0,
            'successful_rows' => 0,
            'error_rows' => $analysis['summary']['rows_failed'],
            'processed_at' => null,
        ]);

        if ($analysis['validation_errors'] !== []) {
            $bulkUpdate->update([
                'status' => ProductBulkUpdate::STATUS_FAILED,
                'processed_at' => now(),
            ]);

            return $bulkUpdate->fresh();
        }

        $summary = [
            'products_updated' => 0,
            'unchanged_rows' => 0,
            'stock_adjustments_created' => 0,
            'rows_failed' => 0,
        ];
        $errors = [];

        foreach ($analysis['prepared_rows'] as $preparedRow) {
            try {
                $rowResult = DB::transaction(function () use ($preparedRow, $user): array {
                    $product = Product::query()->withoutGlobalScopes()->findOrFail($preparedRow['product_id']);
                    $product->fill($preparedRow['updates']);
                    $productChanged = $product->isDirty();

                    if ($productChanged) {
                        $product->save();
                    }

                    $stockAdjustmentsCreated = 0;

                    foreach ($preparedRow['stock_targets'] as $stockTarget) {
                        $this->stockAdjustmentAction->handle($user, [
                            'product_id' => $product->id,
                            'workspace_id' => $stockTarget['workspace_id'],
                            'adjustment_type' => 'set_quantity',
                            'quantity' => $stockTarget['quantity'],
                            'reason' => "Actualizacion masiva de productos (fila {$preparedRow['row']})",
                            'reference' => 'BULK-PRODUCT-UPDATE',
                        ]);

                        $stockAdjustmentsCreated++;
                    }

                    return [
                        'updated' => $productChanged || $stockAdjustmentsCreated > 0,
                        'stock_adjustments_created' => $stockAdjustmentsCreated,
                    ];
                });

                if ($rowResult['updated']) {
                    $summary['products_updated']++;
                } else {
                    $summary['unchanged_rows']++;
                }

                $summary['stock_adjustments_created'] += $rowResult['stock_adjustments_created'];
            } catch (Throwable $exception) {
                $summary['rows_failed']++;
                $errors[] = [
                    'row' => $preparedRow['row'],
                    'field' => 'row',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $bulkUpdate->update([
            'status' => $summary['rows_failed'] > 0 ? ProductBulkUpdate::STATUS_FAILED : ProductBulkUpdate::STATUS_COMPLETED,
            'summary' => $summary,
            'validation_errors' => $errors,
            'processed_rows' => count($analysis['prepared_rows']),
            'successful_rows' => count($analysis['prepared_rows']) - $summary['rows_failed'],
            'error_rows' => $summary['rows_failed'],
            'processed_at' => now(),
        ]);

        return $bulkUpdate->fresh();
    }
}
