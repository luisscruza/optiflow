<?php

declare(strict_types=1);

namespace App\Tables;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Tables\Actions\Action;
use App\Tables\Actions\DeleteAction;
use App\Tables\Actions\EditAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\BadgeColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\SearchFilter;
use App\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;

final class ContactsTable extends Table
{
    protected string $model = Contact::class;

    protected ?string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    protected array $with = ['primaryAddress'];

    protected ?string $rowHref = '/contacts/{id}';

    public function columns(): array
    {
        return [
            TextColumn::make('id', '#')
                ->sortable()
                ->cellClassName('w-20 text-gray-500'),

            BadgeColumn::make('contact_type', 'Tipo')
                ->sortable(),

            TextColumn::make('name', 'Nombre')
                ->sortable()
                ->cellClassName('font-medium')
                ->cellTooltip(fn (Contact $contact) => $contact->name),

            TextColumn::make('identification_number', 'RNC o Cédula')
                ->sortable()
                ->formatUsing(fn ($value) => $value ?: ''),

            TextColumn::make('email', 'Email')
                ->sortable()
                ->formatUsing(fn ($value) => $value ?: ''),

            TextColumn::make('phone_primary', 'Teléfono')
                ->formatUsing(fn ($value) => Str::of($value)
                    ->replaceMatches('/(\+1|1)?[ -.]?(\d{3})[ -.]?(\d{3})[ -.]?(\d{4})/', '($2) $3-$4')->__toString())
                ->sortable(),

            ActionColumn::make()
                ->actions([
                    Action::make('view', 'Ver contacto')
                        ->icon('eye')
                        ->href('/contacts/{id}')
                        ->permission('view contacts'),

                    EditAction::make()
                        ->href('/contacts/{id}/edit')
                        ->permission('edit contacts'),

                    DeleteAction::make()
                        ->href('/contacts/{id}')
                        ->permission('delete contacts')
                        ->requiresConfirmation('¿Está seguro de que desea eliminar este contacto? Esta acción no se puede deshacer.'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['name', 'email', 'identification_number'])
                ->placeholder('Buscar por nombre, email o identificación...'),

            SelectFilter::make('contact_type', 'Tipo')
                ->options(ContactType::class)
                ->default('all')
                ->inline(),
        ];
    }
}
