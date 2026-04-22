<?php

declare(strict_types=1);

namespace App\Tables;

use App\Enums\ExpenseStatus;
use App\Enums\Permission;
use App\Models\Expense;
use App\Models\Workspace;
use App\Tables\Actions\Action;
use App\Tables\Actions\DeleteAction;
use App\Tables\Actions\EditAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\BadgeColumn;
use App\Tables\Columns\CurrencyColumn;
use App\Tables\Columns\DateColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\BooleanFilter;
use App\Tables\Filters\DateRangeFilter;
use App\Tables\Filters\SearchFilter;
use App\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

final class ExpensesTable extends Table
{
    protected string $model = Expense::class;

    protected ?string $defaultSort = 'issue_date';

    protected string $defaultSortDirection = 'desc';

    protected array $with = ['contact', 'workspace'];

    protected ?string $rowHref = '/expenses/{id}';

    public function columns(): array
    {
        $showWorkspace = Auth::user()?->can(Permission::ViewAllLocations) ?? false;

        return [
            TextColumn::make('id', '#')
                ->sortable()
                ->cellClassName('w-20 text-gray-500'),

            TextColumn::make('document_number', 'Comprobante')
                ->sortable()
                ->cellClassName('font-medium')
                ->copiable(),

            TextColumn::make('contact.name', 'Proveedor')
                ->sortable()
                ->cellTooltip(fn (Expense $expense): string => $expense->contact->name),

            TextColumn::make('workspace.name', 'Sucursal')
                ->sortable()
                ->hidden(! $showWorkspace),

            DateColumn::make('issue_date', 'Fecha')
                ->sortable(),

            CurrencyColumn::make('subtotal_amount', 'Subtotal')
                ->sortable(),

            CurrencyColumn::make('total_amount', 'Total neto')
                ->sortable()
                ->headerClassName('font-medium'),

            BadgeColumn::make('status', 'Estado')
                ->sortable(),

            BadgeColumn::make('is_informal', 'Tipo')
                ->labels([
                    '1' => 'Informal',
                    '0' => 'Fiscal',
                ])
                ->colors([
                    '1' => 'yellow',
                    '0' => 'blue',
                ]),

            ActionColumn::make()
                ->actions([
                    Action::make('view', 'Ver gasto')
                        ->icon('eye')
                        ->href('/expenses/{id}')
                        ->permission('view expenses'),

                    EditAction::make()
                        ->href('/expenses/{id}/edit')
                        ->permission('edit expenses'),

                    DeleteAction::make()
                        ->href('/expenses/{id}')
                        ->permission('delete expenses')
                        ->requiresConfirmation('¿Está seguro de que desea eliminar este gasto? Esta acción no se puede deshacer.'),
                ]),
        ];
    }

    public function filters(): array
    {
        $filters = [
            SearchFilter::make('Buscar')
                ->columns(['document_number', 'contact.name', 'workspace.name'])
                ->placeholder('Buscar suplidor, sucursal o comprobante...'),

            SelectFilter::make('status', 'Estado')
                ->options(ExpenseStatus::class)
                ->default('all')
                ->inline(),

            BooleanFilter::make('is_informal', 'Tipo de factura')
                ->trueLabel('Solo informales')
                ->falseLabel('Solo fiscales')
                ->inline(),

            DateRangeFilter::make('issue_date', 'Fecha de gasto'),
        ];

        if (Auth::user()?->can(Permission::ViewAllLocations)) {
            $filters[] = SelectFilter::make('workspace_id', 'Sucursal')
                ->optionsUsing(fn (): array => Workspace::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->default('all')
                ->inline();
        }

        return $filters;
    }
}
