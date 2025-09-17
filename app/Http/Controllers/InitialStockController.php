<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SetInitialStockAction;
use App\Http\Requests\CreateInitialStockRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class InitialStockController extends Controller
{
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

    public function store(#[CurrentUser] User $user, CreateInitialStockRequest $request, SetInitialStockAction $setInitialStockAction): RedirectResponse
    {

        $validated = $request->validated();

        $setInitialStockAction->handle(
            user: $user,
            data: $validated,
        );

        return redirect()
            ->route('initial-stock.index')
            ->with('success', 'Initial stock set successfully.');
    }
}
