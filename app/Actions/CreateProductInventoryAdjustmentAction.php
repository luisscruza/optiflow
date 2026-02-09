<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\ProductInventoryAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateProductInventoryAdjustmentAction
{
    public function __construct(private StockAdjustmentAction $stockAdjustmentAction) {}

    /**
     * @param  array{
     *     workspace_id: int,
     *     adjustment_date: string,
     *     notes?: string|null,
     *     items: array<int, array{product_id: int, adjustment_type: string, quantity: float}>
     * }  $data
     */
    public function handle(User $user, array $data): ProductInventoryAdjustment
    {
        $workspaceId = (int) $data['workspace_id'];

        if (
            ! $user->can(Permission::ViewAllLocations)
            && ! $user->workspaces()->where('workspaces.id', $workspaceId)->exists()
        ) {
            throw new InvalidArgumentException('User does not have access to the selected workspace.');
        }

        return DB::transaction(function () use ($data, $user, $workspaceId): ProductInventoryAdjustment {
            $adjustment = ProductInventoryAdjustment::query()->create([
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'adjustment_date' => $data['adjustment_date'],
                'notes' => $data['notes'] ?? null,
                'total_adjusted' => 0,
            ]);

            $totalAdjusted = 0.0;

            foreach ($data['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $quantity = (float) $item['quantity'];
                $isIncrement = $item['adjustment_type'] === 'increment';
                $currentQuantity = $product->getStockQuantityForWorkspace($workspaceId);
                $finalQuantity = $isIncrement ? $currentQuantity + $quantity : $currentQuantity - $quantity;
                $averageCost = (float) ($product->cost ?? 0);
                $lineTotal = round(($isIncrement ? 1 : -1) * $quantity * $averageCost, 2);

                $this->stockAdjustmentAction->handle($user, [
                    'product_id' => $product->id,
                    'workspace_id' => $workspaceId,
                    'adjustment_type' => $isIncrement ? 'add_quantity' : 'remove_quantity',
                    'quantity' => $quantity,
                    'reason' => $data['notes']
                        ? "Ajuste de inventario #{$adjustment->id}: {$data['notes']}"
                        : "Ajuste de inventario #{$adjustment->id}",
                    'reference' => "ADJ-{$adjustment->id}",
                ]);

                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'adjustment_type' => $item['adjustment_type'],
                    'quantity' => $quantity,
                    'current_quantity' => $currentQuantity,
                    'final_quantity' => $finalQuantity,
                    'average_cost' => $averageCost,
                    'total_adjusted' => $lineTotal,
                ]);

                $totalAdjusted += $lineTotal;
            }

            $adjustment->update([
                'total_adjusted' => round($totalAdjusted, 2),
            ]);

            return $adjustment->load(['workspace', 'createdBy', 'items.product']);
        });
    }
}
