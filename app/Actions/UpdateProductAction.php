<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class UpdateProductAction
{
    /**
     * Update an existing product.
     */
    public function handle(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update([
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'cost' => $data['cost'] ?? null,
                'track_stock' => $data['track_stock'] ?? true,
                'default_tax_id' => $data['default_tax_id'] ?? null,
            ]);

            if ($product->track_stock && ! $product->stockInCurrentWorkspace()->exists()) {
                $product->stocks()->create([
                    'workspace_id' => Auth::user()->current_workspace_id,
                    'quantity' => 0,
                    'minimum_quantity' => 0,
                ]);
            }

            return $product->fresh(['defaultTax', 'stockInCurrentWorkspace']);
        });
    }
}
