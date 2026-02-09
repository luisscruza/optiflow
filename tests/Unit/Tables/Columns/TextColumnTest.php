<?php

declare(strict_types=1);

use App\Tables\Columns\TextColumn;

test('text column is not pinned by default', function (): void {
    $column = new TextColumn('name', 'Nombre');

    expect($column->getDefinition()['pinned'])->toBeFalse();
});

test('text column can be pinned', function (): void {
    $column = (new TextColumn('name', 'Nombre'))->pinned();

    expect($column->getDefinition()['pinned'])->toBeTrue();
});
