<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProductType;
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
                'product_type' => $data['product_type'] ?? ProductType::Product->value,
                'price' => $data['price'],
                'cost' => $data['cost'] ?? null,
                'track_stock' => $data['track_stock']
                    ?? (($data['product_type'] ?? ProductType::Product->value) === ProductType::Product->value),
                'allow_negative_stock' => $data['allow_negative_stock'] ?? false,
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
