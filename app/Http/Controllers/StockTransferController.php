<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StockTransferAction;
use App\Http\Requests\StockTransferRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Tables\StockTransfersTable;
use BackedEnum;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class StockTransferController
{
    public function index(Request $request): Response
    {
        $workspace = Context::get('workspace');

        return Inertia::render('inventory/stock-transfers/index', [
            'transfers' => StockTransfersTable::make($request)->query(
                StockMovement::query()
                    ->withoutWorkspaceScope()
                    ->whereIn('type', StockTransfersTable::transferTypes())
                    ->where(function ($query) use ($workspace): void {
                        $query->where('from_workspace_id', $workspace->id)
                            ->orWhere('to_workspace_id', $workspace->id);
                    })
            ),
            'current_workspace_id' => $workspace->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = Context::get('workspace');

        $products = Product::query()
            ->where('track_stock', true)
            ->whereHas('stocks', function ($query) use ($workspace): void {
                $query->where('workspace_id', $workspace->id)
                    ->where('quantity', '>', 0);
            })
            ->with(['stocks' => function ($query) use ($workspace): void {
                $query->where('workspace_id', $workspace->id);
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $availableWorkspaces = Auth::user()->workspaces()
            ->where('workspaces.id', '!=', $workspace->id)
            ->orderBy('workspaces.name')
            ->get(['workspaces.id', 'workspaces.name']);

        $requestedProductId = $request->integer('product_id');
        $initialProductId = $requestedProductId > 0 && $products->contains('id', $requestedProductId)
            ? $requestedProductId
            : null;

        return Inertia::render('inventory/stock-transfers/create', [
            'products' => $products,
            'availableWorkspaces' => $availableWorkspaces,
            'initial_product_id' => $initialProductId,
        ]);
    }

    public function store(#[CurrentUser] User $user, StockTransferRequest $request, StockTransferAction $stockTransferAction): RedirectResponse
    {
        $validated = $request->validated();

        $stockTransferAction->handle(
            user: $user,
            data: $validated,
        );

        return redirect()
            ->route('stock-transfers.index')
            ->with('success', 'Stock transfer completed successfully.');
    }

    public function show(StockMovement $stockMovement): Response
    {
        $workspace = Context::get('workspace');

        $movementType = $stockMovement->type instanceof BackedEnum
            ? $stockMovement->type->value
            : (string) $stockMovement->type;

        abort_unless(in_array($movementType, StockTransfersTable::transferTypes(), true), 404);

        abort_unless(
            ($stockMovement->from_workspace_id === $workspace->id ||
                $stockMovement->to_workspace_id === $workspace->id),
            404
        );

        $stockMovement->load(['product', 'fromWorkspace', 'toWorkspace', 'createdBy']);

        return Inertia::render('inventory/stock-transfers/show', [
            'transfer' => $stockMovement,
        ]);
    }
}
