<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateProductAction
{
    public function __construct(
        private SetInitialStockAction $setInitialStockAction
    ) {}

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

            // If the product tracks stock and we have initial stock data, set it up
            if ($product->track_stock && $user->current_workspace_id && isset($data['initial_quantity'])) {
                $stockData = [
                    'product_id' => $product->id,
                    'quantity' => $data['initial_quantity'] ?? 0,
                    'minimum_quantity' => $data['minimum_quantity'] ?? null,
                    'unit_cost' => $data['unit_cost'] ?? null,
                    'notes' => 'Initial stock setup during product creation',
                ];

                $this->setInitialStockAction->handle($user, $stockData);
            }

            return $product->load(['defaultTax']);
        });
    }
}
