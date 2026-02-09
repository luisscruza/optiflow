<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class StockAdjustmentAction
{
    /**
     * Ajustar inventario quantity for a product in a workspace.
     *
     * @param  array{product_id: int, workspace_id?: int, adjustment_type: string, quantity: float, reason: string, reference?: string, unit_cost?: float}  $data
     */
    public function handle(User $user, array $data): StockMovement
    {
        $product = Product::query()->findOrFail($data['product_id']);

        if (! $product->track_stock) {
            throw new InvalidArgumentException('Cannot Ajustar inventario for products that do not track inventory.');
        }

        $workspaceId = isset($data['workspace_id']) ? (int) $data['workspace_id'] : $user->current_workspace_id;

        if (! $workspaceId) {
            throw new InvalidArgumentException('User must have an active workspace.');
        }

        if (
            isset($data['workspace_id'])
            && ! $user->can(Permission::ViewAllLocations)
            && ! $user->workspaces()->where('workspaces.id', $workspaceId)->exists()
        ) {
            throw new InvalidArgumentException('User does not have access to the selected workspace.');
        }

        return DB::transaction(function () use ($workspaceId, $product, $data): StockMovement {
            // Get or create stock record for this workspace
            $stock = ProductStock::query()->firstOrCreate([
                'product_id' => $product->id,
                'workspace_id' => $workspaceId,
            ], [
                'quantity' => 0,
                'minimum_quantity' => 0,
            ]);

            // Calculate the adjustment quantity
            $adjustmentQuantity = match ($data['adjustment_type']) {
                'set_quantity' => $data['quantity'] - $stock->quantity,
                'add_quantity' => $data['quantity'],
                'remove_quantity' => -abs((float) $data['quantity']),
                default => throw new InvalidArgumentException('Invalid adjustment type. Must be: set_quantity, add_quantity, or remove_quantity.')
            };

            // Validate we don't go below zero
            $newQuantity = $stock->quantity + $adjustmentQuantity;
            if ($newQuantity < 0) {
                throw new InvalidArgumentException(
                    "Cannot Ajustar inventario to negative quantity. Current: {$stock->quantity}, Adjustment: {$adjustmentQuantity}"
                );
            }

            // Update stock quantity
            $stock->quantity = $newQuantity;
            $stock->save();

            // Create stock movement record
            $movement = StockMovement::query()->create([
                'workspace_id' => $workspaceId,
                'product_id' => $product->id,
                'type' => 'adjustment',
                'quantity' => $adjustmentQuantity,
                'unit_cost' => $data['unit_cost'] ?? null,
                'note' => ($data['reference'] ? "[{$data['reference']}] " : '').$data['reason'],
            ]);

            return $movement->load(['product']);
        });
    }
}
