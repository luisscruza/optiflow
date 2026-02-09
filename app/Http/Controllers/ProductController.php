<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateProductAction;
use App\Actions\DeleteProductAction;
use App\Actions\UpdateProductAction;
use App\Enums\Permission;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use App\Models\Workspace;
use App\Tables\ProductsTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class ProductController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ProductsView), 403);

        return Inertia::render('products/index', [
            'products' => ProductsTable::make($request),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(#[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ProductsCreate), 403);

        return Inertia::render('products/create', [
            'taxes' => Tax::query()->select('id', 'name', 'rate', 'is_default')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateProductRequest $request, #[CurrentUser] User $user, CreateProductAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::ProductsCreate), 403);

        $product = $action->handle(Auth::user(), $request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Producto creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ProductsView), 403);

        $product->load(['defaultTax', 'stockInCurrentWorkspace', 'stockMovements.createdBy']);

        $workspaceStocks = collect();

        if ($product->track_stock) {
            $stockByWorkspace = $product->stocks()
                ->withoutWorkspaceScope()
                ->get(['workspace_id', 'quantity', 'minimum_quantity'])
                ->keyBy('workspace_id');

            $workspacesQuery = Workspace::query()->select(['id', 'name'])->orderBy('name');

            if (! $user->can(Permission::ViewAllLocations)) {
                $workspacesQuery->whereIn('id', $user->workspaces()->select('workspaces.id'));
            }

            $workspaceStocks = $workspacesQuery
                ->get()
                ->map(function (Workspace $workspace) use ($stockByWorkspace): array {
                    $stock = $stockByWorkspace->get($workspace->id);

                    return [
                        'workspace_id' => $workspace->id,
                        'workspace_name' => $workspace->name,
                        'initial_quantity' => 0,
                        'current_quantity' => (float) ($stock?->quantity ?? 0),
                        'minimum_quantity' => (float) ($stock?->minimum_quantity ?? 0),
                        'maximum_quantity' => null,
                    ];
                })
                ->values();
        }

        return Inertia::render('products/show', [
            'product' => $product,
            'workspace_stocks' => $workspaceStocks,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ProductsEdit), 403);

        $product->load(['defaultTax']);

        return Inertia::render('products/edit', [
            'product' => $product,
            'taxes' => Tax::query()->select('id', 'name', 'rate')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product, #[CurrentUser] User $user, UpdateProductAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::ProductsEdit), 403);

        $product = $action->handle($product, $request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product, #[CurrentUser] User $user, DeleteProductAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::ProductsDelete), 403);

        try {
            $action->handle($product);

            return redirect()->route('products.index')
                ->with('success', 'Producto eliminado correctamente.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
