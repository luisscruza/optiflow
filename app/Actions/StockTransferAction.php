<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Exceptions\ReportableActionException;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class StockTransferAction
{
    public function __construct(private ApplyInventoryMovementAction $applyInventoryMovementAction) {}

    /**
     * Transfer stock between workspaces.
     *
     * @param  array{product_id: int, from_workspace_id: int, to_workspace_id: int, quantity: float, reference?: string, notes?: string}  $data
     */
    public function handle(User $user, array $data): array
    {
        $product = Product::query()->findOrFail($data['product_id']);

        if (! $product->track_stock) {
            throw new ReportableActionException('No se puede transferir stock de un producto que no tiene el seguimiento de inventario habilitado.');
        }

        if ($data['from_workspace_id'] === $data['to_workspace_id']) {
            throw new ReportableActionException('No se puede transferir stock a la misma ubicación de trabajo.');
        }

        if ($data['quantity'] <= 0) {
            throw new ReportableActionException('La cantidad a transferir debe ser mayor que cero.');
        }

        // Verify user has access to both workspaces
        $fromWorkspace = Workspace::query()->findOrFail($data['from_workspace_id']);
        $toWorkspace = Workspace::query()->findOrFail($data['to_workspace_id']);

        if ($user->workspaces()->whereIn('workspaces.id', [$fromWorkspace->id, $toWorkspace->id])->count() !== 2) {
            throw new ReportableActionException('El usuario no tiene acceso a ambas ubicaciones de trabajo para realizar la transferencia.');
        }

        return DB::transaction(function () use ($product, $data, $fromWorkspace, $toWorkspace): array {
            // Get source stock record
            $fromStock = ProductStock::query()->withoutGlobalScopes()->where([
                'product_id' => $product->id,
                'workspace_id' => $fromWorkspace->id,
            ])->first();

            if (! $fromStock || $fromStock->quantity < $data['quantity']) {
                $available = $fromStock?->quantity ?? 0;
                throw new ReportableActionException(
                    "No hay suficiente stock en la ubicación de trabajo de origen. Disponible: {$available}, Requerido: {$data['quantity']}."
                );
            }

            $reference = $data['reference'] ?? "TRANSFERENCIA-{$fromWorkspace->id}-{$toWorkspace->id}-".time();
            $note = $data['notes'] ?? "Transferido desde {$fromWorkspace->name} hacia {$toWorkspace->name}";

            $transferOutMovement = $this->applyInventoryMovementAction->handle($product, [
                'workspace_id' => $fromWorkspace->id,
                'quantity' => -abs((float) $data['quantity']),
                'type' => StockMovementType::TRANSFER_OUT,
                'user_id' => Auth::id(),
                'note' => $note,
                'reference_number' => $reference,
                'from_workspace_id' => $fromWorkspace->id,
                'to_workspace_id' => $toWorkspace->id,
            ]);

            $transferInMovement = $this->applyInventoryMovementAction->handle($product, [
                'workspace_id' => $toWorkspace->id,
                'quantity' => abs((float) $data['quantity']),
                'type' => StockMovementType::TRANSFER_IN,
                'user_id' => Auth::id(),
                'note' => $note,
                'reference_number' => $reference,
                'from_workspace_id' => $fromWorkspace->id,
                'to_workspace_id' => $toWorkspace->id,
            ]);

            $toStock = ProductStock::query()->withoutGlobalScopes()->where([
                'product_id' => $product->id,
                'workspace_id' => $toWorkspace->id,
            ])->firstOrFail();

            return [
                'transfer_movement' => $transferOutMovement->load(['product', 'fromWorkspace', 'toWorkspace']),
                'transfer_in_movement' => $transferInMovement->load(['product', 'fromWorkspace', 'toWorkspace']),
                'from_stock' => $fromStock->fresh(),
                'to_stock' => $toStock->fresh(),
            ];
        });
    }
}
