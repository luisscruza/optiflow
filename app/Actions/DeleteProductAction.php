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
    public function handle(Product $product): bool
    {
        return DB::transaction(function () use ($product): bool {
            if ($product->invoiceItems()->exists()) {
                throw new InvalidArgumentException(
                    'Cannot delete product that has been used in invoices or quotations.'
                );
            }

            $hasMovements = $product->stockMovements()
                ->whereNotIn('type', ['initial', 'adjustment'])
                ->exists();

            if ($hasMovements) {
                throw new InvalidArgumentException(
                    'Cannot delete product with stock movement history.'
                );
            }

            $product->stocks()->delete();

            $product->stockMovements()->delete();

            return (bool) $product->delete();
        });
    }
}
