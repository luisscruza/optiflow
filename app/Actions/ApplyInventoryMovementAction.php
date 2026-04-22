<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ReportableActionException;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use BackedEnum;
use Illuminate\Support\Facades\DB;

final class ApplyInventoryMovementAction
{
    /**
     * @param  array{
     *     workspace_id: int,
     *     quantity: float,
     *     type: BackedEnum|string,
     *     user_id?: int|null,
     *     related_invoice_id?: int|null,
     *     note?: string|null,
     *     reference_number?: string|null,
     *     unit_cost?: float|null,
     *     from_workspace_id?: int|null,
     *     to_workspace_id?: int|null
     * }  $data
     */
    public function handle(Product|int $product, array $data): StockMovement
    {
        $productModel = $product instanceof Product
            ? $product
            : Product::query()->findOrFail($product);

        if (! $productModel->track_stock) {
            throw new ReportableActionException('No se puede registrar movimiento para productos que no rastrean inventario.');
        }

        return DB::transaction(function () use ($productModel, $data): StockMovement {
            $workspaceId = (int) $data['workspace_id'];
            $quantity = round((float) $data['quantity'], 2);

            $stock = ProductStock::query()
                ->withoutGlobalScopes()
                ->where('product_id', $productModel->id)
                ->where('workspace_id', $workspaceId)
                ->lockForUpdate()
                ->first();

            if (! $stock instanceof ProductStock) {
                $stock = ProductStock::query()->withoutGlobalScopes()->create([
                    'product_id' => $productModel->id,
                    'workspace_id' => $workspaceId,
                    'quantity' => 0,
                    'minimum_quantity' => 0,
                ]);
            }

            $previousQuantity = round((float) $stock->quantity, 2);
            $currentQuantity = round($previousQuantity + $quantity, 2);

            if (! $productModel->allow_negative_stock && $currentQuantity < 0) {
                throw new ReportableActionException(
                    "No se puede ajustar el stock a un valor negativo. Stock actual: {$previousQuantity}, intento de ajuste: {$quantity}."
                );
            }

            $stock->quantity = $currentQuantity;
            $stock->save();

            $type = $data['type'];
            $typeValue = $type instanceof BackedEnum ? $type->value : (string) $type;

            return StockMovement::query()->withoutGlobalScopes()->create([
                'workspace_id' => $workspaceId,
                'product_id' => $productModel->id,
                'type' => $typeValue,
                'quantity' => $quantity,
                'previous_quantity' => $previousQuantity,
                'current_quantity' => $currentQuantity,
                'unit_cost' => $data['unit_cost'] ?? null,
                'related_invoice_id' => $data['related_invoice_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'note' => $data['note'] ?? null,
                'from_workspace_id' => $data['from_workspace_id'] ?? null,
                'to_workspace_id' => $data['to_workspace_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
            ]);
        });
    }
}
