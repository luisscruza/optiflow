<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DeleteProductAction
{
    /**
     * Delete a product after checking for dependencies.
     */
    public function execute(Product $product): bool
    {
        return DB::transaction(function () use ($product): bool {
            // Check if product has been used in any document items
            if ($product->documentItems()->exists()) {
                throw new InvalidArgumentException(
                    'Cannot delete product that has been used in invoices or quotations.'
                );
            }

            // Check if product has stock movements (except initial stock creation)
            $hasMovements = $product->stockMovements()
                ->whereNotIn('type', ['initial', 'adjustment'])
                ->exists();

            if ($hasMovements) {
                throw new InvalidArgumentException(
                    'Cannot delete product with stock movement history.'
                );
            }

            // Delete related stock records first
            $product->stocks()->delete();

            // Delete any remaining stock movements (initial/adjustment only)
            $product->stockMovements()->delete();

            // Delete the product
            return (bool) $product->delete();
        });
    }
}
