<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StockAdjustmentAction;
use App\Http\Requests\StockAdjustmentRequest;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class StockAdjustmentController extends Controller
{
    public function index(): Response
    {
        $workspace = Context::get('workspace');

        $stockAdjustments = ProductStock::query()
            ->where('workspace_id', $workspace->id)
            ->with(['product'])
            ->paginate(20);

        return Inertia::render('inventory/stock-adjustments/index', [
            'stockAdjustments' => $stockAdjustments,
        ]);
    }

    public function create(): Response
    {

        $products = Product::query()
            ->where('track_stock', true)
            ->with(['stocksInCurrentWorkspace'])
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        // Transform products to include current stock information
        $products->transform(function ($product): \stdClass {
            $stock = $product->stocksInCurrentWorkspace->first();
            $product->stock_in_current_workspace = [
                'quantity' => $stock?->quantity ?? 0,
                'minimum_quantity' => $stock?->minimum_quantity ?? 0,
            ];
            unset($product->stocksInCurrentWorkspace);

            return $product;
        });

        return Inertia::render('inventory/stock-adjustments/create', [
            'products' => $products,
        ]);
    }

    public function store(StockAdjustmentRequest $request, StockAdjustmentAction $stockAdjustmentAction): RedirectResponse
    {
        $stockAdjustmentAction->handle(
            user: Auth::user(),
            data: [
                'product_id' => $request->validated('product_id'),
                'adjustment_type' => $request->validated('adjustment_type'),
                'quantity' => $request->validated('quantity'),
                'reason' => $request->validated('reason'),
                'reference' => $request->validated('reference'),
                'unit_cost' => $request->validated('unit_cost'),
            ]
        );

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
