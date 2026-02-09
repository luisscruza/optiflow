<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateProductInventoryAdjustmentAction;
use App\Enums\Permission;
use App\Http\Requests\StoreProductInventoryAdjustmentRequest;
use App\Models\Product;
use App\Models\ProductInventoryAdjustment;
use App\Models\ProductStock;
use App\Models\User;
use App\Models\Workspace;
use App\Tables\ProductInventoryAdjustmentsTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class ProductInventoryAdjustmentController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InventoryView), 403);

        $workspaceIds = $this->availableWorkspaces($user)->pluck('id');

        return Inertia::render('inventory/inventory-adjustments/index', [
            'adjustments' => ProductInventoryAdjustmentsTable::make($request)->query(
                ProductInventoryAdjustment::query()
                    ->withoutGlobalScope('workspace')
                    ->whereIn('workspace_id', $workspaceIds)
            ),
        ]);
    }

    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InventoryAdjust), 403);

        $workspaces = $this->availableWorkspaces($user);
        $workspaceIds = $workspaces->pluck('id');

        $products = Product::query()
            ->tracksStock()
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'cost']);

        $stockMap = ProductStock::query()
            ->withoutWorkspaceScope()
            ->whereIn('workspace_id', $workspaceIds)
            ->whereIn('product_id', $products->pluck('id'))
            ->get(['product_id', 'workspace_id', 'quantity'])
            ->groupBy('product_id')
            ->map(fn (Collection $rows): array => $rows
                ->mapWithKeys(fn (ProductStock $stock): array => [(string) $stock->workspace_id => (float) $stock->quantity])
                ->all());

        $requestedProductId = $request->integer('product_id');
        $initialProductId = $requestedProductId > 0 && $products->contains('id', $requestedProductId)
            ? $requestedProductId
            : null;

        return Inertia::render('inventory/inventory-adjustments/create', [
            'workspaces' => $workspaces->map(fn (Workspace $workspace): array => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ])->values(),
            'products' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'cost' => (float) ($product->cost ?? 0),
                'stocks_by_workspace' => $stockMap->get($product->id, []),
            ])->values(),
            'current_workspace_id' => $user->current_workspace_id,
            'today' => now()->toDateString(),
            'next_adjustment_number' => (int) (ProductInventoryAdjustment::query()->withoutWorkspaceScope()->max('id') ?? 0) + 1,
            'initial_product_id' => $initialProductId,
        ]);
    }

    public function store(
        StoreProductInventoryAdjustmentRequest $request,
        #[CurrentUser] User $user,
        CreateProductInventoryAdjustmentAction $action
    ): RedirectResponse {
        abort_unless($user->can(Permission::InventoryAdjust), 403);

        $adjustment = $action->handle($user, $request->validated());

        return redirect()
            ->route('inventory-adjustments.show', $adjustment->id)
            ->with('success', "Ajuste de inventario #{$adjustment->id} registrado correctamente.");
    }

    public function show(int $inventoryAdjustment, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InventoryView), 403);

        $adjustment = ProductInventoryAdjustment::query()
            ->withoutGlobalScope('workspace')
            ->with(['workspace', 'createdBy', 'items.product'])
            ->findOrFail($inventoryAdjustment);

        if (
            ! $user->can(Permission::ViewAllLocations)
            && ! $user->workspaces()->where('workspaces.id', $adjustment->workspace_id)->exists()
        ) {
            abort(403);
        }

        return Inertia::render('inventory/inventory-adjustments/show', [
            'adjustment' => $adjustment,
        ]);
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function availableWorkspaces(User $user): Collection
    {
        $workspacesQuery = Workspace::query()->select(['id', 'name'])->orderBy('name');

        if (! $user->can(Permission::ViewAllLocations)) {
            $workspacesQuery->whereIn('id', $user->workspaces()->select('workspaces.id'));
        }

        return $workspacesQuery->get();
    }
}
