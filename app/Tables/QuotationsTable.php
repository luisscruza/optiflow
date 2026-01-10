<?php

declare(strict_types=1);

namespace App\Tables;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Tables\Actions\Action;
use App\Tables\Actions\BulkAction;
use App\Tables\Actions\DeleteAction;
use App\Tables\Actions\EditAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\BadgeColumn;
use App\Tables\Columns\CurrencyColumn;
use App\Tables\Columns\DateColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\DateRangeFilter;
use App\Tables\Filters\SearchFilter;
use App\Tables\Filters\SelectFilter;

final class QuotationsTable extends Table
{
    protected string $model = Quotation::class;

    protected ?string $defaultSort = 'issue_date';

    protected string $defaultSortDirection = 'desc';

    protected array $with = ['contact', 'documentSubtype'];

    protected ?string $rowHref = '/quotations/{id}';

    public function columns(): array
    {
        return [
            TextColumn::make('id', '#')
                ->sortable()
                ->cellClassName('w-20 text-gray-500'),

            TextColumn::make('document_number', 'Número')
                ->sortable(),

            TextColumn::make('contact.name', 'Cliente'),

            DateColumn::make('issue_date', 'Creación')
                ->sortable(),

            DateColumn::make('due_date', 'Vencimiento')
                ->sortable(),

            CurrencyColumn::make('total_amount', 'Total')
                ->sortable(),

            BadgeColumn::make('status', 'Estado')
                ->sortable(),

            ActionColumn::make()
                ->actions([
                    Action::make('print', 'Descargar PDF')
                        ->inline()
                        ->tooltip('Descargar PDF')
                        ->icon('download')
                        ->href('/quotations/{id}/pdf')
                        ->download()
                        ->permission('view quotations'),

                    EditAction::make()
                        ->href('/quotations/{id}/edit')
                        ->permission('edit quotations'),

                    Action::make('convert', 'Convertir a factura')
                        ->color('success')
                        ->icon('refresh-cw')
                        ->href('/quotations/{id}/convert-to-invoice', 'post')
                        ->visibleWhen(fn (Quotation $record) => $record->status !== QuotationStatus::Converted)
                        ->permission('create invoices'),

                    DeleteAction::make()
                        ->href('/quotations/{id}')
                        ->permission('delete quotations')
                        ->visibleWhen(fn (Quotation $record) => $record->status !== QuotationStatus::Converted)
                        ->requiresConfirmation('¿Está seguro de que desea eliminar esta cotización? Esta acción no se puede deshacer.'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['document_number', 'contact.name'])
                ->placeholder('Buscar cliente o número de cotización...'),

            SelectFilter::make('status', 'Estado')
                ->options(QuotationStatus::class)
                ->default('all')
                ->inline(),

            DateRangeFilter::make('issue_date', 'Fecha de creación'),
        ];
    }

    public function bulkActions(): array
    {
        return [
            BulkAction::make('download', 'Descargar PDF')
                ->icon('file')
                ->href('/quotations/bulk/pdf')
                ->permission('view quotations'),
        ];
    }
}
