<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ReportableActionException;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

final class DeleteProductAction
{
    /**
     * Delete a product after checking for dependencies.
     */
    public function handle(Product $product): bool
    {
        return DB::transaction(function () use ($product): bool {
            if ($product->invoiceItems()->exists()) {
                throw new ReportableActionException(
                    'No se puede eliminar un producto que ha sido incluido en facturas.'
                );
            }

            $hasMovements = $product->stockMovements()
                ->whereNotIn('type', ['initial', 'adjustment'])
                ->exists();

            if ($hasMovements) {
                throw new ReportableActionException(
                    'No se puede eliminar un producto que tiene movimientos de stock registrados.'
                );
            }

            $product->stocks()->delete();

            $product->stockMovements()->delete();

            return (bool) $product->delete();
        });
    }
}
