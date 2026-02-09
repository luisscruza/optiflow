<?php

declare(strict_types=1);

namespace App\Tables;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use App\Tables\Actions\ViewAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\DateColumn;
use App\Tables\Columns\NumericColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\SearchFilter;
use Illuminate\Support\Facades\Context;

final class StockTransfersTable extends Table
{
    protected string $model = StockMovement::class;

    protected ?string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected array $with = ['product', 'fromWorkspace', 'toWorkspace', 'createdBy'];

    protected ?string $rowHref = '/stock-transfers/{id}';

    public static function transferTypes(): array
    {
        return [
            StockMovementType::TRANSFER->value,
            StockMovementType::TRANSFER_IN->value,
            StockMovementType::TRANSFER_OUT->value,
        ];
    }

    public function columns(): array
    {
        $currentWorkspaceId = (int) (Context::get('workspace')?->id ?? 0);

        return [
            TextColumn::make('reference_number', 'Numero')
                ->sortable()
                ->formatUsing(fn (?string $value, StockMovement $movement): string => $value ?: "TR-{$movement->id}")
                ->cellClassName('font-semibold text-primary'),

            DateColumn::make('created_at', 'Fecha')
                ->sortable(),

            TextColumn::make('product.name', 'Producto')
                ->sortable()
                ->cellClassName('font-medium')
                ->cellTooltip(fn (StockMovement $movement): string => $movement->product?->sku ?? '-'),

            TextColumn::make('direction', 'Direccion')
                ->formatUsing(fn (mixed $value, StockMovement $movement): string => $movement->from_workspace_id === $currentWorkspaceId ? 'Saliente' : 'Entrante'),

            TextColumn::make('counterpart_workspace', 'Sucursal')
                ->formatUsing(fn (mixed $value, StockMovement $movement): string => $movement->from_workspace_id === $currentWorkspaceId
                    ? ($movement->toWorkspace?->name ?? '-')
                    : ($movement->fromWorkspace?->name ?? '-')),

            NumericColumn::make('quantity', 'Cantidad')
                ->sortable()
                ->alignRight(),

            TextColumn::make('createdBy.name', 'Creado por')
                ->formatUsing(fn (?string $value): string => $value ?: '-'),

            ActionColumn::make()
                ->actions([
                    ViewAction::make()
                        ->href('/stock-transfers/{id}')
                        ->permission('view inventory'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['reference_number', 'product.name', 'product.sku', 'fromWorkspace.name', 'toWorkspace.name', 'note'])
                ->placeholder('Buscar por numero, producto, SKU o sucursal...'),
        ];
    }
}
