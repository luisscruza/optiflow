<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StockAdjustmentAction;
use App\Http\Requests\StockAdjustmentRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class StockAdjustmentController
{
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        return Inertia::location(route('inventory-adjustments.index'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('inventory-adjustments.create');
    }

    public function store(StockAdjustmentRequest $request, StockAdjustmentAction $stockAdjustmentAction): RedirectResponse
    {
        $stockAdjustmentAction->handle(
            user: Auth::user(),
            data: [
                'product_id' => $request->validated('product_id'),
                'workspace_id' => $request->validated('workspace_id'),
                'adjustment_type' => $request->validated('adjustment_type'),
                'quantity' => $request->validated('quantity'),
                'reason' => $request->validated('reason'),
                'reference' => $request->validated('reference'),
                'unit_cost' => $request->validated('unit_cost'),
            ]
        );

        if ($request->boolean('redirect_back')) {
            return redirect()->back()->with('success', 'Stock adjustment completed successfully.');
        }

        return redirect()
            ->route('stock-adjustments.index')
            ->with('success', 'Stock adjustment completed successfully.');
    }

    public function show(Product $product): Response
    {
        $workspace = Context::get('workspace');

        $stockHistory = $product->stockMovements()
            ->withoutWorkspaceScope()
            ->where('workspace_id', $workspace->id)
            ->orWhere(function ($query) use ($workspace): void {
                $query->where('from_workspace_id', $workspace->id)
                    ->orWhere('to_workspace_id', $workspace->id);
            })
            ->with(['fromWorkspace', 'toWorkspace', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $currentStock = $product->stocksInCurrentWorkspace()->first();

        return Inertia::render('inventory/stock-adjustments/show', [
            'product' => $product,
            'currentStock' => $currentStock,
            'stockHistory' => $stockHistory,
        ]);
    }
}
