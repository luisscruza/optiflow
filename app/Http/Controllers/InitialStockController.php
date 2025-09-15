<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SetInitialStockAction;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class InitialStockController extends Controller
{
    public function __construct(
        private readonly SetInitialStockAction $setInitialStockAction
    ) {}

    public function index(): Response
    {
        $workspace = Context::get('workspace');

        $productsWithStock = Product::query()
            ->where('track_stock', true)
            ->with(['stocks' => function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            }])
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('inventory/initial-stock/index', [
            'productsWithStock' => $productsWithStock,
        ]);
    }

    public function create(): Response
    {
        $workspace = Context::get('workspace');

        $products = Product::query()
            ->where('track_stock', true)
            ->whereDoesntHave('stocks', function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return Inertia::render('inventory/initial-stock/create', [
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('track_stock', true);
                }),
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],
            'unit_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'note' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $this->setInitialStockAction->handle(
            user: Auth::user(),
            data: [
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'unit_cost' => $validated['unit_cost'] ?? null,
                'reason' => $validated['note'] ?? 'Initial stock setup',
            ]
        );

        return redirect()
            ->route('initial-stock.index')
            ->with('success', 'Initial stock set successfully.');
    }

    public function show(Product $product): Response
    {
        $workspace = Context::get('workspace');

        $currentStock = $product->stocks()
            ->where('workspace_id', $workspace->id)
            ->first();

        $initialStockMovement = $product->stockMovements()
            ->where('type', 'initial')
            ->first();

        return Inertia::render('inventory/initial-stock/show', [
            'product' => $product,
            'currentStock' => $currentStock,
            'initialStockMovement' => $initialStockMovement,
        ]);
    }
}
