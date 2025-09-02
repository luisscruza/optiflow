<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateProductAction
{
    /**
     * Create a new product.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Product
    {
        return DB::transaction(function () use ($user, $data) {
            // Create the product
            $product = Product::create([
                'name' => $data['name'],
                'sku' => $data['sku'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'cost' => $data['cost'] ?? null,
                'track_stock' => $data['track_stock'] ?? true,
                'default_tax_id' => $data['default_tax_id'] ?? null,
            ]);

            // If the product tracks stock, create initial stock record for current workspace
            if ($product->track_stock && $user->current_workspace_id) {
                ProductStock::create([
                    'product_id' => $product->id,
                    'workspace_id' => $user->current_workspace_id,
                    'quantity' => $data['initial_stock'] ?? 0,
                    'minimum_quantity' => $data['minimum_quantity'] ?? 5,
                ]);
            }

            return $product->load(['defaultTax']);
        });
    }
}
