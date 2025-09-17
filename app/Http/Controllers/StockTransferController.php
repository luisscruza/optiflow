<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StockTransferAction;
use App\Http\Requests\StockTransferRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class StockTransferController extends Controller
{
    public function index(): Response
    {
        $workspace = Context::get('workspace');

        $transfers = StockMovement::query()
            ->withoutWorkspaceScope()
            ->where('type', 'transfer')
            ->where(function ($query) use ($workspace) {
                $query->where('from_workspace_id', $workspace->id)
                    ->orWhere('to_workspace_id', $workspace->id);
            })
            ->with(['product', 'fromWorkspace', 'toWorkspace', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('inventory/stock-transfers/index', [
            'transfers' => $transfers,
        ]);
    }

    public function create(): Response
    {
        $workspace = Context::get('workspace');

        $products = Product::query()
            ->where('track_stock', true)
            ->whereHas('stocks', function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id)
                    ->where('quantity', '>', 0);
            })
            ->with(['stocks' => function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $availableWorkspaces = Auth::user()->workspaces()
            ->where('workspaces.id', '!=', $workspace->id)
            ->orderBy('workspaces.name')
            ->get(['workspaces.id', 'workspaces.name']);

        return Inertia::render('inventory/stock-transfers/create', [
            'products' => $products,
            'availableWorkspaces' => $availableWorkspaces,
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

        abort_unless(
            ($stockMovement->from_workspace_id === $workspace->id ||
            $stockMovement->to_workspace_id === $workspace->id),
            404
        );

        $stockMovement->load(['product', 'fromWorkspace', 'toWorkspace']);

        return Inertia::render('inventory/stock-transfers/show', [
            'transfer' => $stockMovement,
        ]);
    }
}
