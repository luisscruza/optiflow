<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Product;
use App\Tables\Actions\Action;
use App\Tables\Actions\DeleteAction;
use App\Tables\Actions\EditAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\CurrencyColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\BooleanFilter;
use App\Tables\Filters\SearchFilter;

final class ProductsTable extends Table
{
    protected string $model = Product::class;

    protected ?string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected array $with = ['defaultTax', 'stockInCurrentWorkspace'];

    protected ?string $rowHref = '/products/{id}';

    public function columns(): array
    {
        return [
            TextColumn::make('id', '#')
                ->sortable()
                ->cellClassName('w-20 text-gray-500'),

            TextColumn::make('name', 'Producto')
                ->sortable()
                ->cellClassName('font-medium')
                ->formatUsing(function ($value, Product $product) {
                    if ($product->description) {
                        return $value . ' • ' . $product->description;
                    }

                    return $value;
                })
                ->cellTooltip(fn(Product $product) => $product->description ? $product->name . ' - ' . $product->description : $product->name),

            TextColumn::make('sku', 'SKU')
                ->sortable()
                ->cellClassName('font-mono text-sm'),

            CurrencyColumn::make('price', 'Precio')
                ->sortable()
                ->cellClassName('font-medium'),

            TextColumn::make('cost', 'Costo')
                ->formatUsing(function ($value) {
                    if ($value) {
                        return number_format((float) $value, 2);
                    }

                    return '-';
                })
                ->cellClassName('text-sm text-gray-500'),

            TextColumn::make('inventory', 'Inventario')
                ->formatUsing(function ($value, Product $product) {
                    if (! $product->track_stock) {
                        return 'Sin seguimiento';
                    }

                    if (! $product->stockInCurrentWorkspace) {
                        return 'Sin datos';
                    }

                    $stock = $product->stockInCurrentWorkspace;
                    $quantity = $stock->quantity;
                    $isLow = $quantity <= $stock->minimum_quantity;

                    return [
                        'quantity' => $quantity,
                        'isLow' => $isLow,
                    ];
                }),

            TextColumn::make('tax', 'Impuesto')
                ->formatUsing(function ($value, Product $product) {
                    if ($product->defaultTax) {
                        return $product->defaultTax->name . ' (' . $product->defaultTax->rate . '%)';
                    }

                    return 'Sin impuesto';
                })
                ->cellClassName('text-sm'),

            ActionColumn::make()
                ->actions([
                    Action::make('view', 'Ver producto')
                        ->icon('eye')
                        ->href('/products/{id}')
                        ->permission('view products'),

                    EditAction::make()
                        ->href('/products/{id}/edit')
                        ->permission('edit products'),

                    Action::make('stock', 'Ver movimientos')
                        ->icon('package')
                        ->href('/products/{id}')
                        ->permission('view inventory')
                        ->visibleWhen(fn(Product $product) => $product->track_stock),

                    DeleteAction::make()
                        ->href('/products/{id}')
                        ->permission('delete products')
                        ->requiresConfirmation('¿Está seguro de que desea eliminar este producto? Esta acción no se puede deshacer.'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['name', 'sku', 'description'])
                ->placeholder('Buscar por nombre, SKU o descripción...'),

            BooleanFilter::make('track_stock', 'Solo productos con inventario')
                ->query(function ($query, $value) {
                    if ($value === '1' || $value === 'true' || $value === true) {
                        $query->where('track_stock', true);
                    }
                }),

            BooleanFilter::make('low_stock', 'Solo inventario bajo')
                ->query(function ($query, $value) {
                    if ($value === '1' || $value === 'true' || $value === true) {
                        $query->whereHas('stockInCurrentWorkspace', function ($q) {
                            $q->whereColumn('quantity', '<=', 'minimum_quantity');
                        });
                    }
                }),
        ];
    }
}
