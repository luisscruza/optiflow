<?php

declare(strict_types=1);

namespace App\Tables;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Tables\Actions\Action;
use App\Tables\Actions\DeleteAction;
use App\Tables\Actions\EditAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\BadgeColumn;
use App\Tables\Columns\CurrencyColumn;
use App\Tables\Columns\DateColumn;
use App\Tables\Columns\TextColumn;
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
            TextColumn::make('id', '# Interno')
                ->sortable()
                ->className('w-20 text-gray-500'),

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
                ->className('font-medium'),

            BadgeColumn::make('status', 'Estado')
                ->sortable(),

            ActionColumn::make()
                ->actions([
                    Action::make('print', 'Imprimir')
                        ->icon('printer')
                        ->href('/invoices/{id}/pdf')
                        ->permission('view invoices'),
                    Action::make('payment', 'Registrar pago')
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
                ->default('all'),
        ];
    }
}
