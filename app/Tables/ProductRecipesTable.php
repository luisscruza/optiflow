<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\ProductRecipe;
use App\Tables\Actions\Action;
use App\Tables\Columns\ActionColumn;
use App\Tables\Columns\DateColumn;
use App\Tables\Columns\TextColumn;
use App\Tables\Filters\DateRangeFilter;
use App\Tables\Filters\SearchFilter;

final class ProductRecipesTable extends Table
{
    protected string $model = ProductRecipe::class;

    protected ?string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    protected array $with = ['contact', 'optometrist', 'product', 'workspace'];

    public function columns(): array
    {
        return [
            TextColumn::make('id', '#')
                ->sortable()
                ->cellClassName('w-20 text-gray-500'),

            TextColumn::make('contact.name', 'Contacto'),

            TextColumn::make('product.name', 'Producto'),

            TextColumn::make('optometrist.name', 'Evaluador'),

            DateColumn::make('created_at', 'Fecha')
                ->sortable(),

            ActionColumn::make()
                ->actions([
                     Action::make('print', 'Imprimir PDF')
                        ->inline()
                        ->tooltip('Imprimir PDF')
                        ->icon('printer')
                        ->target('_blank')
                        ->href('/product-recipes/{id}/pdf')
                        ->permission('view prescriptions'),
                    Action::make('print', 'Descargar PDF')
                        ->inline()
                        ->tooltip('Descargar PDF')
                        ->icon('download')
                        ->href('/product-recipes/{id}/pdf')
                        ->download()
                        ->permission('view prescriptions'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            SearchFilter::make('Buscar')
                ->columns(['id', 'contact.name', 'product.name', 'optometrist.name'])
                ->placeholder('Buscar contacto, producto o evaluador...'),
            DateRangeFilter::make('created_at', 'Fecha'),
        ];
    }
}
