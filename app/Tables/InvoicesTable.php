<?php

declare(strict_types=1);

namespace App\Tables;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Tables\Actions\Action;
use App\Tables\Actions\BulkAction;
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

final class InvoicesTable extends Table
{
    protected string $model = Invoice::class;

    protected ?string $defaultSort = 'issue_date';

    protected string $defaultSortDirection = 'desc';

    protected array $with = ['contact', 'documentSubtype'];

    protected ?string $rowHref = '/invoices/{id}';

    public function columns(): array
    {
        return [
            TextColumn::make('id', '#')
                ->sortable()
                ->cellClassName('w-20 text-gray-500'),

            TextColumn::make('document_number', 'NCF/Número')
                ->sortable(),

            TextColumn::make('contact.name', 'Cliente'),

            DateColumn::make('issue_date', 'Creación')
                ->sortable(),

            DateColumn::make('due_date', 'Vencimiento')
                ->sortable(),

            CurrencyColumn::make('total_amount', 'Total')
                ->sortable(),

            CurrencyColumn::make('amount_due', 'Por cobrar')
                ->sortable()
                ->headerClassName('font-medium'),

            BadgeColumn::make('status', 'Estado')
                ->sortable(),

            ActionColumn::make()
                ->actions([
                    Action::make('print', 'Descargar PDF')
                        ->icon('download')
                        ->href('/invoices/{id}/pdf')
                        ->download()
                        ->permission('view invoices'),
                    Action::make('payment', 'Registrar pago')
                        ->tooltip('Registrar pago')
                        ->icon('dollar')
                        ->handler('openPaymentModal')
                        ->visibleWhen(fn(Invoice $invoice) => $invoice->canRegisterPayment())
                        ->permission('create payments')
                        ->inline(),

                    EditAction::make()
                        ->href('/invoices/{id}/edit')
                        ->visibleWhen(fn(Invoice $invoice) => $invoice->canBeEdited())
                        ->permission('edit invoices'),

                    DeleteAction::make()
                        ->href('/invoices/{id}')
                        ->permission('delete invoices')
                        ->visibleWhen(fn(Invoice $invoice) => $invoice->canBeDeleted())
                        ->requiresConfirmation('¿Está seguro de que desea eliminar esta factura? Esta acción no se puede deshacer.'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['document_number', 'contact.name'])
                ->placeholder('Buscar cliente o número de factura...'),

            SelectFilter::make('status', 'Estado')
                ->options(InvoiceStatus::class)
                ->default('all')
                ->inline(),

            BooleanFilter::make('overdue', 'Vencidas')
                ->query(function ($query, $value) {
                    if ($value === '1' || $value === 'true' || $value === true) {
                        $query->where('status', '!=', InvoiceStatus::Paid->value)
                            ->where('due_date', '<=', now());
                    }
                }),

            DateRangeFilter::make('issue_date', 'Fecha de creación'),
        ];
    }

    public function bulkActions(): array
    {
        return [
            BulkAction::make('download', 'Descargar PDF')
                ->icon('file')
                ->href('/invoices/bulk/pdf')
                ->permission('view invoices'),
        ];
    }
}

