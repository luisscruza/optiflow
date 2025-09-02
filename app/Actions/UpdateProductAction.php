<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

final class UpdateProductAction
{
    /**
     * Update an existing product.
     */
    public function execute(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            // Update the product
            $product->update([
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'cost' => $data['cost'] ?? null,
                'track_stock' => $data['track_stock'] ?? true,
                'default_tax_id' => $data['default_tax_id'] ?? null,
            ]);

            // If stock tracking has been enabled and there's no stock record for this workspace,
            // create one with zero quantity
            if ($product->track_stock && ! $product->stockInCurrentWorkspace()->exists()) {
                $product->stocks()->create([
                    'workspace_id' => $product->workspace_id,
                    'current_stock' => 0,
                    'reserved_stock' => 0,
                    'reorder_level' => 0,
                ]);
            }

            // Reload the product with its relationships
            return $product->fresh(['defaultTax', 'stockInCurrentWorkspace']);
        });
    }
}
