<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SetInitialStockAction
{
    /**
     * Set initial stock for a product in a workspace.
     *
     * @param  array{product_id: int, quantity: float, minimum_quantity?: float, unit_cost?: float, notes?: string}  $data
     */
    public function handle(User $user, array $data): ProductStock
    {
        $product = Product::findOrFail($data['product_id']);

        if (! $product->track_stock) {
            throw new InvalidArgumentException('Cannot set initial stock for products that do not track inventory.');
        }

        if (! $user->current_workspace_id) {
            throw new InvalidArgumentException('User must have an active workspace.');
        }

        if ($data['quantity'] < 0) {
            throw new InvalidArgumentException('Initial stock quantity cannot be negative.');
        }

        return DB::transaction(function () use ($user, $product, $data): ProductStock {
            // Check if stock record already exists
            $existingStock = ProductStock::where([
                'product_id' => $product->id,
                'workspace_id' => $user->current_workspace_id,
            ])->first();

            if ($existingStock) {
                throw new InvalidArgumentException(
                    "Initial stock already set for this product in the current workspace. Current quantity: {$existingStock->quantity}. Use stock adjustment instead."
                );
            }

            // Create stock record
            $stock = ProductStock::create([
                'product_id' => $product->id,
                'workspace_id' => $user->current_workspace_id,
                'quantity' => $data['quantity'],
                'minimum_quantity' => $data['minimum_quantity'] ?? 0,
            ]);

            // Only create movement if quantity > 0
            if ($data['quantity'] > 0) {
                StockMovement::create([
                    'workspace_id' => $user->current_workspace_id,
                    'product_id' => $product->id,
                    'type' => 'initial',
                    'quantity' => $data['quantity'],
                    'unit_cost' => $data['unit_cost'] ?? null,
                    'note' => $data['notes'] ?? 'Initial stock setup',
                ]);
            }

            return $stock->load(['product']);
        });
    }
}
