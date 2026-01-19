<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Mastertable;
use App\Tables\Actions\DeleteAction;
use App\Tables\Actions\EditAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\DateColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\DateRangeFilter;
use App\Tables\Filters\SearchFilter;

final class MastertablesTable extends Table
{
    protected string $model = Mastertable::class;

    protected ?string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected array $with = [];

    protected array $withCount = ['items'];

    protected ?string $rowHref = '/mastertables/{id}';

    public function columns(): array
    {
        return [
            TextColumn::make('id', '#')
                ->sortable()
                ->cellClassName('w-20 text-gray-500'),

            TextColumn::make('name', 'Nombre')
                ->sortable(),

            TextColumn::make('alias', 'Identificador')
                ->sortable()
                ->cellClassName('font-mono text-sm text-gray-600 dark:text-gray-400'),

            TextColumn::make('items_count', 'Elementos')
                ->sortable()
                ->cellClassName('text-center'),

            DateColumn::make('created_at', 'Creación')
                ->sortable(),

            ActionColumn::make()
                ->actions([
                    EditAction::make()
                        ->href('/mastertables/{id}/edit')
                        ->permission('edit mastertables'),

                    DeleteAction::make()
                        ->href('/mastertables/{id}')
                        ->permission('delete mastertables')
                        ->requiresConfirmation('¿Está seguro de que desea eliminar esta tabla maestra? Todos sus elementos también serán eliminados. Esta acción no se puede deshacer.'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['name', 'alias'])
                ->placeholder('Buscar por nombre o alias...'),
            DateRangeFilter::make('created_at', 'Fecha de creación'),
        ];
    }
}
