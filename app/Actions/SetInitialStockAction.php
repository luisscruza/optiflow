<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SetInitialStockAction
{
    /**
     * Set initial stock for a product in a workspace.
     *
     * @param  array{product_id: int, quantity: float, minimum_quantity?: float, unit_cost?: float, notes?: string}  $data
     */
    public function handle(?User $user, array $data, ?Workspace $workspace = null): ProductStock
    {
        $product = Product::findOrFail($data['product_id']);

        if (! $product->track_stock) {
            throw new InvalidArgumentException('Cannot set initial stock for products that do not track inventory.');
        }

        $workspaceId = $workspace?->id ?? $user?->current_workspace_id;

        // if ($data['quantity'] < 0) {
        //     throw new InvalidArgumentException('Initial stock quantity cannot be negative.');
        // }

        return DB::transaction(function () use ($product, $data, $workspaceId): ProductStock {
            $existingStock = ProductStock::where([
                'product_id' => $product->id,
                'workspace_id' => $workspaceId,
            ])->first();

            if ($existingStock) {
                throw new InvalidArgumentException(
                    "Initial stock already set for this product in the current workspace. Current quantity: {$existingStock->quantity}. Use stock adjustment instead."
                );
            }

            $stock = ProductStock::create([
                'product_id' => $product->id,
                'workspace_id' => $workspaceId,
                'quantity' => $data['quantity'],
                'minimum_quantity' => $data['minimum_quantity'] ?? 0,
            ]);

            if ($data['quantity']) {
                StockMovement::create([
                    'workspace_id' => $workspaceId,
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
