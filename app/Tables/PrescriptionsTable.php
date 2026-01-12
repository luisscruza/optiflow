<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Prescription;
use App\Tables\Actions\Action;
use App\Tables\Actions\DeleteAction;
use App\Tables\Actions\EditAction;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\DateColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\DateRangeFilter;
use App\Tables\Filters\SearchFilter;

final class PrescriptionsTable extends Table
{
    protected string $model = Prescription::class;

    protected ?string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected array $with = ['patient', 'workspace'];

    protected ?string $rowHref = '/prescriptions/{id}';

    public function columns(): array
    {
        return [
            TextColumn::make('id', '#')
                ->sortable()
                ->cellClassName('w-20 text-gray-500'),

            TextColumn::make('patient.name', 'Paciente'),

            DateColumn::make('created_at', 'Creación')
                ->sortable(),

            TextColumn::make('workspace.name', 'Sucursal'),


            ActionColumn::make()
                ->actions([
                    Action::make('print', 'Descargar PDF')
                        ->inline()
                        ->tooltip('Descargar PDF')
                        ->icon('download')
                        ->href('/prescriptions/{id}/pdf')
                        ->download()
                        ->permission('view prescriptions'),

                    EditAction::make()
                        ->href('/prescriptions/{id}/edit')
                        ->permission('edit prescriptions'),

                    DeleteAction::make()
                        ->href('/prescriptions/{id}')
                        ->permission('delete prescriptions')
                        ->requiresConfirmation('¿Está seguro de que desea eliminar esta receta? Esta acción no se puede deshacer.'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['id', 'patient.name'])
                ->placeholder('Buscar paciente o número de receta...'),
            DateRangeFilter::make('created_at', 'Fecha de creación'),
        ];
    }
}
