<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class SetInitialStockAction
{
    public function __construct(private ApplyInventoryMovementAction $applyInventoryMovementAction) {}

    /**
     * Set initial stock for a product in a workspace.
     *
     * @param  array{product_id: int, quantity: float, minimum_quantity?: float, unit_cost?: float, notes?: string}  $data
     */
    public function handle(?User $user, array $data, ?Workspace $workspace = null): ProductStock
    {
        $product = Product::query()->findOrFail($data['product_id']);

        if (! $product->track_stock) {
            throw new InvalidArgumentException('Cannot set initial stock for products that do not track inventory.');
        }

        $workspaceId = $workspace?->id ?? $user?->current_workspace_id;

        // if ($data['quantity'] < 0) {
        //     throw new InvalidArgumentException('Initial stock quantity cannot be negative.');
        // }

        return DB::transaction(function () use ($product, $data, $workspaceId, $user): ProductStock {
            $existingStock = ProductStock::query()->where([
                'product_id' => $product->id,
                'workspace_id' => $workspaceId,
            ])->first();

            if ($existingStock) {
                throw new InvalidArgumentException(
                    "Initial stock already set for this product in the current workspace. Current quantity: {$existingStock->quantity}. Use stock adjustment instead."
                );
            }

            $stock = ProductStock::query()->create([
                'product_id' => $product->id,
                'workspace_id' => $workspaceId,
                'quantity' => 0,
                'minimum_quantity' => $data['minimum_quantity'] ?? 0,
            ]);

            if ($data['quantity']) {
                $this->applyInventoryMovementAction->handle($product, [
                    'workspace_id' => $workspaceId,
                    'quantity' => (float) $data['quantity'],
                    'type' => 'initial',
                    'user_id' => $user?->id,
                    'unit_cost' => $data['unit_cost'] ?? null,
                    'note' => $data['notes'] ?? 'Initial stock setup',
                ]);
            }

            return $stock->load(['product']);
        });
    }
}
