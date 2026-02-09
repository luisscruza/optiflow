<?php

declare(strict_types=1);

namespace App\Tables;

use App\Enums\Permission;
use App\Models\ProductInventoryAdjustment;
use App\Models\User;
use App\Models\Workspace;
use App\Tables\Actions\ViewAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\CurrencyColumn;
use App\Tables\Columns\DateColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\SearchFilter;
use App\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

final class ProductInventoryAdjustmentsTable extends Table
{
    protected string $model = ProductInventoryAdjustment::class;

    protected ?string $defaultSort = 'adjustment_date';

    protected string $defaultSortDirection = 'desc';

    protected array $with = ['workspace'];

    protected ?string $rowHref = '/inventory-adjustments/{id}';

    public function columns(): array
    {
        return [
            TextColumn::make('id', 'Numero')
                ->sortable()
                ->cellClassName('w-24 font-semibold text-primary'),

            DateColumn::make('adjustment_date', 'Fecha')
                ->sortable(),

            TextColumn::make('workspace.name', 'Almacen')
                ->cellClassName('max-w-40 whitespace-normal break-words')
                ->cellTooltip(fn (ProductInventoryAdjustment $adjustment): string => $adjustment->workspace?->name ?? '-'),

            CurrencyColumn::make('total_adjusted', 'Total ajustado')
                ->sortable()
                ->cellClassName('font-medium'),

            TextColumn::make('notes', 'Observaciones')
                ->formatUsing(fn (?string $value): string => $value ?: '-')
                ->cellClassName('max-w-72 truncate')
                ->cellTooltip(fn (ProductInventoryAdjustment $adjustment): string => $adjustment->notes ?: '-'),

            ActionColumn::make()
                ->actions([
                    ViewAction::make()
                        ->href('/inventory-adjustments/{id}')
                        ->permission('view inventory'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['id', 'notes', 'workspace.name'])
                ->placeholder('Buscar por numero, almacen u observaciones...'),

            SelectFilter::make('workspace_id', 'Sucursal')
                ->optionsUsing(function (): array {
                    /** @var User|null $user */
                    $user = Auth::user();

                    if (! $user instanceof User) {
                        return [];
                    }

                    $query = Workspace::query()->select(['id', 'name'])->orderBy('name');

                    if (! $user->can(Permission::ViewAllLocations)) {
                        $query->whereIn('id', $user->workspaces()->select('workspaces.id'));
                    }

                    return $query->pluck('name', 'id')->toArray();
                }),
        ];
    }
}
