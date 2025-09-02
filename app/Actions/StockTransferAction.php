<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Workspace;
use Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class StockTransferAction
{
    /**
     * Transfer stock between workspaces.
     *
     * @param  array{product_id: int, from_workspace_id: int, to_workspace_id: int, quantity: float, reference?: string, notes?: string}  $data
     */
    public function handle(User $user, array $data): array
    {
        $product = Product::findOrFail($data['product_id']);

        if (! $product->track_stock) {
            throw new InvalidArgumentException('Cannot transfer stock for products that do not track inventory.');
        }

        if ($data['from_workspace_id'] === $data['to_workspace_id']) {
            throw new InvalidArgumentException('Cannot transfer stock to the same workspace.');
        }

        if ($data['quantity'] <= 0) {
            throw new InvalidArgumentException('Transfer quantity must be greater than zero.');
        }

        // Verify user has access to both workspaces
        $fromWorkspace = Workspace::findOrFail($data['from_workspace_id']);
        $toWorkspace = Workspace::findOrFail($data['to_workspace_id']);

        if (! $user->workspaces()->whereIn('workspaces.id', [$fromWorkspace->id, $toWorkspace->id])->count() === 2) {
            throw new InvalidArgumentException('User does not have access to one or both workspaces.');
        }

        return DB::transaction(function () use ($product, $data, $fromWorkspace, $toWorkspace): array {
            // Get source stock record
            $fromStock = ProductStock::withoutGlobalScopes()->where([
                'product_id' => $product->id,
                'workspace_id' => $fromWorkspace->id,
            ])->first();

            if (! $fromStock || $fromStock->quantity < $data['quantity']) {
                $available = $fromStock?->quantity ?? 0;
                throw new InvalidArgumentException(
                    "Insufficient stock in source workspace. Available: {$available}, Requested: {$data['quantity']}"
                );
            }

            // Get or create destination stock record
            $toStock = ProductStock::withoutGlobalScopes()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'workspace_id' => $toWorkspace->id,
                ],
                [
                    'quantity' => 0,
                    'minimum_quantity' => 0,
                ]
            );

            // Update stock quantities
            $fromStock->quantity -= $data['quantity'];
            $fromStock->save();

            $toStock->quantity += $data['quantity'];
            $toStock->save();

            $reference = $data['reference'] ?? "TRANSFER-{$fromWorkspace->id}-{$toWorkspace->id}-".time();

            // Create a single transfer movement record
            $transferMovement = StockMovement::withoutGlobalScopes()->create([
                'workspace_id' => $fromWorkspace->id, // Primary workspace (where it's initiated from)
                'product_id' => $product->id,
                'type' => 'transfer',
                'quantity' => $data['quantity'],
                'from_workspace_id' => $fromWorkspace->id,
                'to_workspace_id' => $toWorkspace->id,
                'reference_number' => $reference,
                'note' => $data['notes'] ?? "Transfer from {$fromWorkspace->name} to {$toWorkspace->name}",
                'user_id' => Auth::id(),
            ]);

            return [
                'transfer_movement' => $transferMovement->load(['product', 'fromWorkspace', 'toWorkspace']),
                'from_stock' => $fromStock->fresh(),
                'to_stock' => $toStock->fresh(),
            ];
        });
    }
}
