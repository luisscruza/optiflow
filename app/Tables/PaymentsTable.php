<?php

declare(strict_types=1);

namespace App\Tables;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\BankAccount;
use App\Models\Payment;
use App\Tables\Actions\Action;
use App\Tables\Actions\DeleteAction;
use App\Tables\Actions\ViewAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\BadgeColumn;
use App\Tables\Columns\CurrencyColumn;
use App\Tables\Columns\DateColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\DateRangeFilter;
use App\Tables\Filters\SearchFilter;
use App\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class PaymentsTable extends Table
{
    protected string $model = Payment::class;

    protected ?string $defaultSort = 'payment_date';

    protected string $defaultSortDirection = 'desc';

    protected array $with = ['bankAccount', 'currency', 'invoice.contact', 'contact'];

    protected ?string $rowHref = '/payments/{id}';

    public function columns(): array
    {
        return [
            TextColumn::make('id', '#')
                ->sortable()
                ->cellClassName('w-20 text-gray-500'),

            TextColumn::make('payment_number', 'Número')
                ->sortable(),

            BadgeColumn::make('payment_type', 'Tipo')
                ->sortable()
                ->labels(PaymentType::options())
                ->colors([
                    PaymentType::InvoicePayment->value => 'blue',
                    PaymentType::OtherIncome->value => 'yellow',
                ]),

            TextColumn::make('contact_name', 'Cliente/Contacto')
                ->cellClassName('max-w-32 font-bold truncate')
                ->formatUsing(fn ($value, Payment $record) => $record->invoice?->contact?->name ?? $record->contact?->name ?? '-'),

            DateColumn::make('payment_date', 'Fecha')
                ->sortable(),

            TextColumn::make('bank_account.name', 'Cuenta')
                ->cellClassName('truncate'),

            BadgeColumn::make('payment_method', 'Método')
                ->sortable()
                ->labels(PaymentMethod::options())
                ->colors([
                    PaymentMethod::Cash->value => 'green',
                    PaymentMethod::Check->value => 'blue',
                    PaymentMethod::CreditCard->value => 'blue',
                    PaymentMethod::BankTransfer->value => 'blue',
                    PaymentMethod::MobilePayment->value => 'blue',
                    PaymentMethod::Transfer->value => 'blue',
                    PaymentMethod::Other->value => 'gray',
                ]),

            CurrencyColumn::make('amount', 'Monto')
                ->sortable()
                ->headerClassName('font-medium'),

            BadgeColumn::make('status', 'Estado')
                ->sortable()
                ->labels(PaymentStatus::options())
                ->colors([
                    PaymentStatus::Completed->value => 'green',
                    PaymentStatus::Voided->value => 'red',
                ]),

            ActionColumn::make()
                ->actions([
                    ViewAction::make()
                        ->href('/payments/{id}')
                        ->permission('view payments'),

                    Action::make('pdf', 'Descargar PDF')
                        ->icon('download')
                        ->href('/payments/{id}/pdf')
                        ->download()
                        ->permission('view payments'),

                    DeleteAction::make()
                        ->href('/payments/{id}')
                        ->permission('delete payments')
                        ->visibleWhen(fn (Payment $payment) => $payment->isCompleted())
                        ->requiresConfirmation('¿Está seguro de que desea anular este pago? Esta acción no se puede deshacer.'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['payment_number', 'note', 'contact.name', 'invoice.contact.name'])
                ->placeholder('Buscar por número de pago, cliente o nota...'),

            SelectFilter::make('payment_type', 'Tipo de pago')
                ->options(PaymentType::class)
                ->default('all')
                ->inline(),

            SelectFilter::make('payment_method', 'Método de pago')
                ->options(PaymentMethod::class)
                ->default('all')
                ->inline(),

            SelectFilter::make('bank_account_id', 'Cuenta bancaria')
                ->optionsUsing(fn (): array => BankAccount::onlyActive()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->mapWithKeys(fn (string $name, int $id) => [(string) $id => $name])
                    ->toArray())
                ->default('all')
                ->inline(),

            DateRangeFilter::make('payment_date', 'Fecha de pago'),
        ];
    }

    protected function getQuery(): Builder
    {
        return parent::getQuery()->completed();
    }
}
