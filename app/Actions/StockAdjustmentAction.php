<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Permission;
use App\Enums\StockMovementType;
use App\Exceptions\ReportableActionException;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;

final readonly class StockAdjustmentAction
{
    public function __construct(private ApplyInventoryMovementAction $applyInventoryMovementAction) {}

    /**
     * Ajustar inventario quantity for a product in a workspace.
     *
     * @param  array{product_id: int, workspace_id?: int, adjustment_type: string, quantity: float, reason: string, reference?: string, unit_cost?: float}  $data
     */
    public function handle(User $user, array $data): StockMovement
    {
        $product = Product::query()->findOrFail($data['product_id']);

        if (! $product->track_stock) {
            throw new ReportableActionException('No se puede ajustar el inventario para productos que no rastrean inventario.');
        }

        $workspaceId = isset($data['workspace_id']) ? (int) $data['workspace_id'] : $user->current_workspace_id;

        if (! $workspaceId) {
            throw new ReportableActionException('User must have an active workspace.');
        }

        if (
            isset($data['workspace_id'])
            && ! $user->can(Permission::ViewAllLocations)
            && ! $user->workspaces()->where('workspaces.id', $workspaceId)->exists()
        ) {
            throw new ReportableActionException('User does not have access to the selected workspace.');
        }

        $currentQuantity = (float) (ProductStock::query()
            ->withoutGlobalScopes()
            ->where('product_id', $product->id)
            ->where('workspace_id', $workspaceId)
            ->value('quantity') ?? 0);

        $adjustmentQuantity = match ($data['adjustment_type']) {
            'set_quantity' => $data['quantity'] - $currentQuantity,
            'add_quantity' => $data['quantity'],
            'remove_quantity' => -abs((float) $data['quantity']),
            default => throw new ReportableActionException('Tipo de ajuste inválido. Debe ser: set_quantity, add_quantity, o remove_quantity.')
        };

        return $this->applyInventoryMovementAction->handle($product, [
            'workspace_id' => $workspaceId,
            'quantity' => $adjustmentQuantity,
            'type' => StockMovementType::ADJUSTMENT,
            'user_id' => $user->id,
            'unit_cost' => $data['unit_cost'] ?? null,
            'note' => $data['reason'],
            'reference_number' => $data['reference'] ?? null,
        ])->load(['product']);
    }
}
