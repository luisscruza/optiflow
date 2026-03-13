<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\StoreProductRecipeRequest;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\ProductRecipe;
use App\Models\User;
use App\Support\ContactSearch;
use App\Tables\ProductRecipesTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class ProductRecipeController
{
    public function index(Request $request, #[CurrentUser] User $user, ContactSearch $contactSearch): Response
    {
        abort_unless($user->can(Permission::PrescriptionsView), 403);

        $productsMastertable = Mastertable::query()
            ->with(['items' => fn ($query) => $query->orderBy('name')])
            ->where('alias', ProductRecipe::PRODUCTS_MASTERTABLE_ALIAS)
            ->first();

        return inertia('product-recipes/index', [
            'productRecipes' => ProductRecipesTable::make($request),
            'productsMastertable' => $productsMastertable ? [
                'id' => $productsMastertable->id,
                'name' => $productsMastertable->name,
                'alias' => $productsMastertable->alias,
                'description' => $productsMastertable->description,
                'items' => $productsMastertable->items
                    ->map(fn (MastertableItem $item): array => [
                        'id' => $item->id,
                        'mastertable_id' => $item->mastertable_id,
                        'name' => $item->name,
                    ])->values()->all(),
            ] : null,
            'contactSearchResults' => Inertia::optional(fn (): array => $contactSearch->search((string) $request->string('contact_search'), ['customer'])),
            'optometristSearchResults' => Inertia::optional(fn (): array => $contactSearch->search((string) $request->string('optometrist_search'), ['optometrist'])),
        ]);
    }

    public function store(StoreProductRecipeRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::PrescriptionsCreate), 403);

        $workspace = Context::get('workspace');

        abort_if($workspace === null, 404);

        ProductRecipe::query()->create([
            ...$request->validated(),
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
        ]);

        return to_route('product-recipes.index')->with('success', 'Recetario de productos creado correctamente.');
    }
}
