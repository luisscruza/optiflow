<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Workspace;
use App\Tables\Columns\NumericColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\BooleanFilter;
use App\Tables\Filters\SearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ConsolidatedStockTable extends Table
{
    protected string $model = Product::class;

    protected ?string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    protected ?string $rowHref = '/products/{id}';

    /**
     * @var Collection<int, Workspace>
     */
    private Collection $workspaces;

    /**
     * @param  Collection<int, Workspace>  $workspaces
     */
    public function forWorkspaces(Collection $workspaces): static
    {
        $this->workspaces = $workspaces;

        $workspaceIds = $workspaces->modelKeys();

        $this->with = [
            'stocks' => fn ($query) => $query->withoutWorkspaceScope()->whereIn('workspace_id', $workspaceIds),
        ];

        return $this->query(
            Product::query()
                ->tracksStock()
                ->withSum([
                    'stocks as total_quantity' => fn ($query) => $query->withoutWorkspaceScope()->whereIn('workspace_id', $workspaceIds),
                ], 'quantity')
        );
    }

    public function columns(): array
    {
        $workspaceColumns = $this->workspaces->map(
            fn (Workspace $workspace): NumericColumn => NumericColumn::make("stock_{$workspace->id}", $workspace->name)
                ->alignRight()
                ->formatUsing(fn ($value, Product $product): float => $this->quantityIn($product, $workspace))
        )->all();

        return [
            TextColumn::make('name', 'Producto')
                ->pinned()
                ->sortable()
                ->cellClassName('font-medium'),

            TextColumn::make('sku', 'Referencia')
                ->sortable()
                ->copiable()
                ->cellClassName('font-mono text-sm'),

            ...$workspaceColumns,

            NumericColumn::make('total_quantity', 'Total')
                ->alignRight()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy('total_quantity', $direction))
                ->formatUsing(fn ($value): float => (float) $value)
                ->cellClassName('font-semibold'),
        ];
    }

    public function filters(): array
    {
        $workspaceIds = $this->workspaces->modelKeys();

        return [
            SearchFilter::make('Buscar')
                ->columns(['name', 'sku'])
                ->placeholder('Buscar por nombre o referencia...'),

            BooleanFilter::make('in_stock', 'Solo con existencia')
                ->query(function (Builder $query, $value) use ($workspaceIds): void {
                    if ($value === '1' || $value === 'true' || $value === true) {
                        $query->whereHas('stocks', fn ($stockQuery) => $stockQuery
                            ->withoutWorkspaceScope()
                            ->whereIn('workspace_id', $workspaceIds)
                            ->where('quantity', '>', 0));
                    }
                }),
        ];
    }

    private function quantityIn(Product $product, Workspace $workspace): float
    {
        /** @var ProductStock|null $stock */
        $stock = $product->stocks->firstWhere('workspace_id', $workspace->id);

        return (float) ($stock->quantity ?? 0);
    }
}
